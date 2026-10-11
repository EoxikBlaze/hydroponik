<?php

namespace App\Console\Commands;

use App\Models\AlertLog;
use App\Models\Monitoring;
use App\Services\NotifWaService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Ringkasan sensor pagi — rata-rata24 jam dikirim ke WhatsApp sekali sehari,
 * target jam07:00 WITA.
 *
 * Dijalankan otomatis oleh:
 *   1. scheduler harian di routes/console.php (->timezone('Asia/Makassar'))
 *      — hanya jalan jika hosting punya cron `schedule:run`;
 *   2. piggyback dari POST /api/save-sensor (ESP32 polling rutin) dengan
 *      cache lock1 jam — JALUR INI yang diandalkan di panel BEKANTANJANTAN
 *      yang tanpa cron scheduler. Selama ESP32 nyambung, ringkasan tetap
 *      terkirim walau tanpa schedule:run.
 *
 * Guard: kunci AlertLog per tanggal WITA ("ringkasan|YYYY-MM-DD") ->
 * tepat satu kali per hari. Kalau kirim GAGAL, kunci TIDAK dicatat ->
 * otomatis dicoba lagi pada jam berikutnya (masih hari yang sama).
 */
#[Signature('sensor:ringkasan {--force : Kirim tanpa cek jam (untuk tes)} {--dry-run : Tampilkan pesan tanpa mengirim}')]
#[Description('Kirim ringkasan rata-rata sensor24 jam ke WhatsApp (sekali sehari, jam07:00 WITA)')]
class RingkasanPagi extends Command
{
    /** Jam pengiriman (WITA) & zona waktu — WITA = UTC+8. */
    private const JAM_KIRIM = '07:00';
    private const ZONA = 'Asia/Makassar';

    public function handle(): int
    {
        $kini = now(self::ZONA);

        // Sebelum jam07:00 WITA -> lewatkan. Sesudahnya tetap boleh (sebagai
        // cadangan kalau jam07:00 ESP32 sedang offline), dibatasi guard harian.
        if (! $this->option('force') && $kini->format('H:i') < self::JAM_KIRIM) {
            $this->info('ringkasan: belum jam '.self::JAM_KIRIM.' WITA.');

            return self::SUCCESS;
        }

        $kunci = 'ringkasan|'.$kini->toDateString();
        if (AlertLog::sudahTerkirim('ringkasan', $kunci)) {
            $this->info('ringkasan: hari ini sudah terkirim.');

            return self::SUCCESS;
        }

        $data = $this->agregat24Jam();

        if ($this->option('dry-run')) {
            $this->line($this->susunPesan($data));
            $this->info('DRY-RUN — tidak dikirim, tidak dicatat.');

            return self::SUCCESS;
        }

        $wa = app(NotifWaService::class);
        if ($wa->tolak('ringkasan', 'rata-rata sensor') !== null) {
            $this->warn('Tipe ringkasan tidak ada di whitelist WA — dilewati.');

            return self::SUCCESS;
        }

        if (! $wa->kirim($this->susunPesan($data))) {
            // Gagal / belum aktif -> JANGAN dicatat supaya bisa dicoba lagi
            // pada siklus berikutnya (masih hari yang sama).
            $this->warn('WhatsApp belum aktif / gagal terkirim — akan dicoba lagi.');

            return self::SUCCESS;
        }

        AlertLog::simpan('ringkasan', $kunci);
        $this->info("ringkasan: terkirim ({$data['jumlah']} pembacaan24 jam).");
        Log::info('[WA] ringkasan pagi terkirim', ['pembacaan' => $data['jumlah']]);

        return self::SUCCESS;
    }

    /** Rata-rata + rentang tiap sensor, distribusi level air, & event solenoid. */
    private function agregat24Jam(): array
    {
        $sejak = now()->subDay();

        $rata = Monitoring::where('created_at', '>=', $sejak)
            ->selectRaw('COUNT(*) AS jumlah, AVG(waterTemp) AS wt, MIN(waterTemp) AS wt_min, MAX(waterTemp) AS wt_max,
                AVG(ph) AS ph, MIN(ph) AS ph_min, MAX(ph) AS ph_max,
                AVG(tds) AS tds, MIN(tds) AS tds_min, MAX(tds) AS tds_max,
                AVG(airTemp) AS at, MIN(airTemp) AS at_min, MAX(airTemp) AS at_max,
                AVG(humidity) AS hum, MIN(humidity) AS hum_min, MAX(humidity) AS hum_max')
            ->first();

        $level = Monitoring::where('created_at', '>=', $sejak)
            ->selectRaw('water_level, COUNT(*) AS jml')
            ->groupBy('water_level')
            ->pluck('jml', 'water_level');

        $solenoid = AlertLog::where('type', 'solenoid')
            ->where('created_at', '>=', $sejak)
            ->get()
            ->groupBy(fn ($a) => str_starts_with(strtolower($a->value), 'on') ? 'on' : 'off')
            ->map->count();

        $terbaru = Monitoring::latest('created_at')->value('water_level');
        $kritis = in_array(strtoupper((string) $terbaru), ['ERROR', 'LOCKOUT'], true);

        return [
            'jumlah' => (int) ($rata->jumlah ?? 0),
            'wt' => $rata, 'level' => $level, 'solenoid' => $solenoid,
            'kritis' => $kritis, 'terbaru' => $terbaru,
        ];
    }

    /** Susun pesan ringkasan (rapi untuk layar HP). */
    private function susunPesan(array $d): string
    {
        $baris = function (string $ikon, string $nama, ?string $avg, string $unit, $r = null): string {
            if ($avg === null) {
                return "{$ikon} {$nama} : —";
            }
            $rentang = is_array($r) ? " ({$r[0]}–{$r[1]})" : '';

            return "{$ikon} {$nama} : {$avg} {$unit}{$rentang}";
        };

        $a = $d['wt'];
        $f = fn ($v, $n) => $v === null ? null : number_format((float) $v, $n);

        if ($d['jumlah'] === 0) {
            $isi = "Belum ada data sensor dalam24 jam terakhir.\n"
                .'Pastikan ESP32 terkoneksi ke internet.';
        } else {
            $isi = $baris('🌡️', 'Suhu air', $f($a->wt, 1), '°C', [$f($a->wt_min, 1), $f($a->wt_max, 1)])."\n"
                .$baris('🧪', 'pH', $f($a->ph, 2), '', [$f($a->ph_min, 1), $f($a->ph_max, 1)])."\n"
                .$baris('🧂', 'TDS', $f($a->tds, 0), 'ppm', [$f($a->tds_min, 0), $f($a->tds_max, 0)])."\n"
                .$baris('🌤️', 'Suhu udara', $f($a->at, 1), '°C', [$f($a->at_min, 1), $f($a->at_max, 1)])."\n"
                .$baris('💦', 'Kelembapan', $f($a->hum, 0), '%', [$f($a->hum_min, 0), $f($a->hum_max, 0)])."\n";

            if ($d['level']->isNotEmpty()) {
                $isi .= '💧 Level air : '.collect($d['level'])
                    ->map(fn ($j, $l) => ($l ?: '-')." {$j}×")
                    ->implode(' · ')."\n";
            }
            $on = $d['solenoid']['on'] ?? 0;
            $off = $d['solenoid']['off'] ?? 0;
            $isi .= "🚰 Solenoid ON/OFF : {$on} / {$off}\n";
            $isi .= "📋 {$d['jumlah']} pembacaan (24 jam terakhir)";
        }

        $peringatan = $d['kritis']
            ? "\n⚠️ Status terkini: ".strtoupper((string) $d['terbaru']).' — periksa pelampung & solenoid tandon!'
            : '';

        return "🌿 *HarvestHouse*\n"
            ."📊 RINGKASAN SENSOR PAGI\n"
            ."───────────────────\n"
            .$isi.$peringatan."\n"
            ."───────────────────\n"
            .now(self::ZONA)->format('d M Y').' · '.self::JAM_KIRIM.' WITA';
    }
}
