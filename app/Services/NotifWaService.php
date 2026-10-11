<?php

namespace App\Services;

use App\Models\WaSetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Pengirim notifikasi WhatsApp via Fonnte (https://fonnte.com).
 *
 * KENAPA FONNTE: layanan gateway WA yang resmi & stabil (bukan scraping),
 * satu token API dari dashboard Fonnte, bisa kirim ke banyak nomor sekaligus.
 *
 * API key & nomor tujuan DIATUR LEWAT WEBSITE (menu "Notifikasi WhatsApp"),
 * disimpan di tabel wa_settings — TIDAK di .env — supaya bisa diganti tanpa
 * akses ke server.
 *
 * AKTIVASI (sekali saja):
 *   1. Daftar di https://fonnte.com lalu pasang aplikasinya di HP (device).
 *   2. Salin TOKEN dari dashboard Fonnte.
 *   3. Di website HarvestHouse: menu Notifikasi WhatsApp -> isi API key,
 *      isi nomor tujuan (boleh lebih dari satu, pisahkan dengan koma),
 *      centang "Aktifkan", lalu tekan "Kirim Pesan Uji".
 *
 * Endpoint: POST https://api.fonnte.com/send
 *   header : Authorization: <token>
 *   form   : target (nomor dipisah koma), message, countryCode=62, delay
 */
class NotifWaService
{
    /** Jeda minimal (detik) antar pesan agar tidak kena rate limit Fonnte. */
    private const JEDA_DETIK = 3;

    /** Notifikasi siap kirim? (aktif + API key + minimal satu nomor tujuan) */
    public function aktif(): bool
    {
        $s = WaSetting::data();

        return $s->aktif
            && trim((string) $s->api_key) !== ''
            && count($s->daftarTarget()) > 0;
    }

    /**
     * Tipe & nilai yang BOLEH masuk WhatsApp (whitelist dari config).
     *
     * Permintaan: hanya solenoid ON/OFF, water_level Penuh/Kosong, dan
     * jadwal pemindahan meja. Tipe lain (ph, tds, suhu, kelembapan, emergency)
     * TETAP tercatat ke AlertLog untuk riwayat web — hanya tidak dikirim ke WA
     * supaya ponsel tidak dibanjiri.
     *
     * @return string|null alasan ditolak, atau null jika lolos
     */
    public function tolak(string $type, string $value): ?string
    {
        $whitelist = config('services.fonnte.types', []);

        if (! in_array($type, $whitelist, true)) {
            return "tipe '{$type}' tidak ada di whitelist";
        }

        // water_level: hanya Penuh & Kurang (kosong). "Sedang" bukan peristiwa
        // yang perlu diingatkan, jadi dibuang agar tidak membanjiri.
        if ($type === 'water_level') {
            $v = strtolower(trim($value));
            if (! in_array($v, ['penuh', 'kurang', 'kosong', 'empty', 'full'], true)) {
                return "nilai water_level '{$value}' bukan Penuh/Kosong";
            }
        }

        return null;
    }

    /**
     * Kirim satu pesan. Aman dipanggil kapan saja — jika belum dikonfigurasi
     * atau gagal, hanya dicatat ke log (tak melempar exception, tak memblokir).
     *
     * @return bool true jika Fonnte menerima pesan (HTTP 200 + status true)
     */
    public function kirim(string $pesan): bool
    {
        if (! $this->aktif()) {
            Log::debug('[WA] dinonaktifkan / belum dikonfigurasi');

            return false;
        }

        $s = WaSetting::data();

        try {
            $this->jedaKiriman();

            $r = Http::asForm()
                ->timeout(15)
                ->retry(2, 800)
                ->withHeaders(['Authorization' => trim((string) $s->api_key)])
                ->post(config('services.fonnte.endpoint', 'https://api.fonnte.com/send'), [
                    'target' => implode(',', $s->daftarTarget()),
                    'message' => $pesan,
                    'countryCode' => '62',
                    'delay' => '1',
                ]);

            // Fonnte selalu balas JSON {"status":true/false,...}.
            // "status":false (token salah, device putus, dst) = GAGAL,
            // walau HTTP-nya 200 — jangan dianggap terkirim.
            $status = $r->json('status');

            if ($r->successful() && in_array($status, [true, 'true'], true)) {
                Log::info('[WA] terkirim via Fonnte', ['resp' => $r->body()]);

                return true;
            }

            Log::warning('[WA] Fonnte gagal', ['code' => $r->status(), 'body' => $r->body()]);

            return false;
        } catch (\Throwable $e) {
            Log::warning('[WA] exception: '.$e->getMessage());

            return false;
        }
    }

    /**
     * Kirim satu alert (tipe+nilai) lewat jalur whitelist — satu pintu untuk
     * semua sumber: alert hardware (ESP32), perintah manual kran, & pindah tanam.
     * Gagal / ditolak whitelist -> hanya log. Tidak pernah melempar exception.
     */
    public function kirimAlert(string $type, string $value): bool
    {
        try {
            $ditolak = $this->tolak($type, $value);
            if ($ditolak !== null) {
                Log::debug('[WA] dilewati (bukan target notifikasi): '.$ditolak);

                return false;
            }

            return $this->kirim($this->formatPesan($type, $value));
        } catch (\Throwable $e) {
            Log::warning('[WA] gagal kirim alert: '.$e->getMessage());

            return false;
        }
    }

    /**
     * Catat ke AlertLog (riwayat web) + kirim ke WhatsApp, dengan cooldown
     * anti-spam. Dipakai peristiwa NON-hardware: perintah manual kran &
     * pindah tanam. Nilai dipotong 100 karakter (kapasitas kolom alert_logs).
     */
    public function catatDanKirim(string $type, string $value, int $cooldownDetik = 60): bool
    {
        try {
            if (mb_strlen($value) > 100) {
                $value = mb_substr($value, 0, 97).'...';
            }

            if (! \App\Models\AlertLog::bolehKirim($type, $value, $cooldownDetik)) {
                return false;
            }

            \App\Models\AlertLog::simpan($type, $value);

            return $this->kirimAlert($type, $value);
        } catch (\Throwable $e) {
            Log::warning('[WA] catat/kirim alert gagal: '.$e->getMessage());

            return false;
        }
    }

    /** Pesan uji untuk tombol "Kirim Pesan Uji" di halaman pengaturan. */
    public function pesanUji(): string
    {
        return "🌿 *HarvestHouse*\n✅ PESAN UJI NOTIFIKASI\nPengaturan WhatsApp (Fonnte) berhasil — Anda akan menerima peringatan kebun di nomor ini.\n".now('Asia/Makassar')->format('d M Y H:i').' WITA';
    }

    /** Format pesan alert agar enak dibaca di HP. */
    public function formatPesan(string $type, string $value): string
    {
        $judul = match ($type) {
            'water_level' => '💧 LEVEL AIR',
            'solenoid' => '🚰 SOLENOID',
            'jadwal' => '📅 JADWAL PINDAH MEJA',
            'pindah_tanam' => '🌱 PINDAH TANAM',
            'ringkasan' => '📊 RINGKASAN SENSOR',
            'ph', 'tds' => strtoupper($type).' NUTRISI',
            'suhu_air', 'waterTemp' => '🌡️ SUHU AIR',
            'airTemp' => '🌤️ SUHU UDARA',
            'humidity' => '💦 KELEMBAPAN',
            'emergency' => '🚨 EMERGENCY',
            'switch_error' => '⚠️ SWITCH ERROR',
            'lockout' => '🔒 LOCKOUT',
            default => strtoupper($type),
        };

        // Susulan status supaya pesan terbaca utuh tanpa membuka aplikasi.
        $label = match (strtolower(trim($value))) {
            'penuh' => 'Penuh ✅',
            'kurang', 'kosong' => 'Kosong / Kurang ⚠️',
            'on' => 'ON (menyala) 💧',
            'on (manual)' => 'ON — perintah manual dari web 💧',
            'off (manual)' => 'OFF — perintah manual dari web ⛔',
            'off' => 'OFF (mati) ⛔',
            default => $value,
        };

        return "🌿 *HarvestHouse*\n{$judul}\n{$label}\n".now('Asia/Makassar')->format('d M Y H:i').' WITA';
    }

    /** Rate limit: tahan pesan agar tak melebihi kuota Fonnte. */
    private function jedaKiriman(): void
    {
        $lock = sys_get_temp_dir().'/wa_fonnte.lock';
        $last = @file_get_contents($lock);

        if ($last !== false && (time() - (int) $last) < self::JEDA_DETIK) {
            sleep(self::JEDA_DETIK - (time() - (int) $last));
        }

        @file_put_contents($lock, (string) time());
    }
}
