<?php

namespace App\Console\Commands;

use App\Models\AlertLog;
use App\Models\Pendewasaan;
use App\Models\Peremajaan;
use App\Models\Semai;
use App\Services\NotifWaService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Pengingat H-1 jadwal pemindahan meja / panen.
 *
 * Dijalankan otomatis oleh:
 *   1. scheduler harian (routes/console.php) — butuh cron `schedule:run`
 *   2. pemanggilan piggyback dari POST /api/save-sensor (ESP32 polling),
 *      dibatasi cache lock 1 jam — jalur ini yang diandalkan di hosting
 *      tanpa cron scheduler.
 *
 * Kenapa dedup sendiri (bukan AlertLog::bolehKirim): bolehKirim hanya
 * membandingkan SATU baris terakhir per tipe. Beberapa jadwal berbeda pada
 * hari yang sama saling menggeser "baris terakhir" sehingga terkirim ulang.
 * Di sini tiap jadwal punya kunci sendiri -> tepat satu kali per tahap.
 */
#[Signature('jadwal:h1 {--dry-run : Tampilkan tanpa kirim WhatsApp}')]
#[Description('Kirim pengingat H-1 jadwal pemindahan meja (semua tahap yang jatuh tempo)')]
class JadwalH1 extends Command
{
    public function handle(): int
    {
        // Cakup H-1 (besok) dan H-0 (hari ini) supaya jadwal yang kelewat
        // scheduler kemarin tetap sempat diingatkan hari ini.
        $wita = now('Asia/Makassar');
        $rentang = [$wita->toDateString(), $wita->addDay()->toDateString()];

        $kandidat = array_merge(
            $this->kandidatSemai($rentang),
            $this->kandidatPeremajaan($rentang),
            $this->kandidatPendewasaan($rentang),
        );

        if ($kandidat === []) {
            $this->info('jadwal:h1 — tidak ada jadwal jatuh tempo dalam 2 hari ke depan.');

            return self::SUCCESS;
        }

        // Buang yang sudah pernah diingatkan (kunci per tahap, sekali seumur tahap).
        $belum = array_values(array_filter(
            $kandidat,
            fn (array $k) => ! AlertLog::sudahTerkirim('jadwal', $k['kunci']),
        ));

        if ($belum === []) {
            $this->info('jadwal:h1 — semua '.count($kandidat).' jadwal sudah pernah diingatkan.');

            return self::SUCCESS;
        }

        $pesan = $this->susunPesan($belum);

        if ($this->option('dry-run')) {
            $this->line($pesan);
            $this->info('DRY-RUM — tidak dikirim, tidak dicatat.');

            return self::SUCCESS;
        }

        $wa = app(NotifWaService::class);
        if ($wa->tolak('jadwal', 'H-1') !== null) {
            $this->warn('Tipe jadwal tidak ada di whitelist WA — dilewati.');

            return self::SUCCESS;
        }

        if (! $wa->kirim($pesan)) {
            // Gagal / belum aktif -> JANGAN dicatat, agar bisa dicoba lagi
            // pada siklus berikutnya (mis. setelah .env diisi & aktif).
            $this->warn('WhatsApp belum aktif / gagal terkirim — jadwal dicatat ulang nanti.');

            return self::SUCCESS;
        }

        foreach ($belum as $k) {
            AlertLog::simpan('jadwal', $k['kunci']);
        }

        $this->info('jadwal:h1 — '.count($belum).' pengingat terkirim.');
        Log::info('[WA] jadwal H-1 terkirim', ['jumlah' => count($belum)]);

        return self::SUCCESS;
    }

    /** Semai yang akan selesai -> pindah ke meja Peremajaan. */
    private function kandidatSemai(array $rentang): array
    {
        return Semai::whereIn('tgl_akhir_semai', $rentang)
            ->where('status', '!=', 'gagal')
            ->whereDoesntHave('peremajaan')
            ->with(['tanaman', 'meja'])
            ->get()
            ->map(fn (Semai $s) => [
                'kunci' => "jadwal|semai|{$s->id_semai}",
                'ikon' => '🌱',
                'nama' => $s->tanaman?->nama_tanaman ?? 'Tanaman',
                'batch' => $s->id_semai,
                'dari' => 'Semai',
                'ke' => 'Peremajaan',
                'meja' => $s->meja?->meja ?? '-',
                'tgl' => $s->tgl_akhir_semai,
            ])
            ->all();
    }

    /** Peremajaan yang akan selesai -> pindah ke meja Pendewasaan. */
    private function kandidatPeremajaan(array $rentang): array
    {
        return Peremajaan::whereIn('tgl_akhir_peremajaan', $rentang)
            ->whereDoesntHave('pendewasaan')
            ->with(['semai.tanaman', 'meja'])
            ->get()
            ->map(fn (Peremajaan $p) => [
                'kunci' => "jadwal|peremajaan|{$p->id_peremajaan}",
                'ikon' => '🌿',
                'nama' => $p->semai?->tanaman?->nama_tanaman ?? 'Tanaman',
                'batch' => $p->semai?->id_semai,
                'dari' => 'Peremajaan',
                'ke' => 'Pendewasaan',
                'meja' => $p->meja?->meja ?? '-',
                'tgl' => $p->tgl_akhir_peremajaan,
            ])
            ->all();
    }

    /** Pendewasaan yang akan selesai -> Panen (tahap terakhir). */
    private function kandidatPendewasaan(array $rentang): array
    {
        return Pendewasaan::whereIn('tgl_akhir_pendewasaan', $rentang)
            ->whereDoesntHave('panen')
            ->with(['peremajaan.semai.tanaman', 'meja'])
            ->get()
            ->map(fn (Pendewasaan $d) => [
                'kunci' => "jadwal|pendewasaan|{$d->id_pendewasaan}",
                'ikon' => '🌾',
                'nama' => $d->peremajaan?->semai?->tanaman?->nama_tanaman ?? 'Tanaman',
                'batch' => $d->peremajaan?->semai?->id_semai,
                'dari' => 'Pendewasaan',
                'ke' => 'Panen',
                'meja' => $d->meja?->meja ?? '-',
                'tgl' => $d->tgl_akhir_pendewasaan,
            ])
            ->all();
    }

    /** Satu pesan berisi semua jadwal jatuh tempo (hemat rate limit Fonnte). */
    private function susunPesan(array $items): string
    {
        $besok = now('Asia/Makassar')->addDay()->toDateString();

        $baris = [];
        foreach ($items as $i => $k) {
            $penekan = $k['tgl'] === $besok ? 'besok' : 'HARI INI';
            $baris[] = ($i + 1).". {$k['ikon']} {$k['nama']} · Batch #{$k['batch']}\n"
                ."    {$k['dari']} → {$k['ke']} · {$k['meja']}\n"
                .'    '.date('d M Y', strtotime($k['tgl']))." ({$penekan})";
        }

        return "🌿 *HarvestHouse*\n"
            ."📅 PENGINGAT JADWAL PINDAH MEJA\n"
            ."───────────────────\n"
            .implode("\n", $baris)."\n"
            ."───────────────────\n"
            ."Tandai selesai di menu *Tanam Saya* sebelum tanggal tersebut.\n"
            .now('Asia/Makassar')->format('d M Y H:i').' WITA';
    }
}
