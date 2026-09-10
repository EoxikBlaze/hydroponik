<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Models\{Semai, Peremajaan, Pendewasaan};
use App\Services\{WhatsAppService, LaporanService};

// ----------------------------------------------------
// COMMAND: Kirim Laporan Sensor Rutin (6 Jam)
// ----------------------------------------------------
Artisan::command('wa:report', function () {
    $this->info('Mengirim laporan sensor 6 jam ke WhatsApp...');
    $service = new LaporanService();
    $message = $service->generate();

    $wa = new WhatsAppService();
    $berhasil = $wa->sendToAll($message);

    $this->info("Laporan terkirim ke {$berhasil} nomor penerima.");
})->purpose('Kirim ringkasan laporan sensor 6 jam terakhir ke WhatsApp');

// ----------------------------------------------------
// COMMAND: Kirim Notifikasi Pengingat H-1 Siklus
// ----------------------------------------------------
Artisan::command('wa:h-minus-1', function () {
    $this->info('Mengecek pengingat siklus H-1...');
    $wa = new WhatsAppService();
    $tomorrow = now()->addDay()->toDateString();
    $terkirim = 0;

    // 1. H-1 SEMAI
    $semaiList = Semai::with('tanaman')
        ->whereDate('tgl_akhir_semai', $tomorrow)
        ->where('status_notif', '!=', 'sent')
        ->get();

    foreach ($semaiList as $s) {
        $msg = "🌱 *PENGINGAT H-1 PINDAH TANAM SEMAI*\n"
             . "━━━━━━━━━━━━━━━━━━\n"
             . "Tanaman  : " . ($s->tanaman->nama_tanaman ?? 'Tanaman') . "\n"
             . "Jumlah   : {$s->jumlah_benih} benih\n"
             . "Tgl Akhir: {$s->tgl_akhir_semai}\n"
             . "Status   : Siap dipindahkan ke meja peremajaan besok.\n"
             . "━━━━━━━━━━━━━━━━━━\n"
             . "⏱️ " . now()->format('d-m-Y H:i');

        $cnt = $wa->sendToAll($msg);
        if ($cnt > 0) {
            $s->update(['status_notif' => 'sent']);
            $terkirim += $cnt;
        }
    }

    // 2. H-1 PEREMAJAAN
    $peremajaanList = Peremajaan::with(['semai.tanaman', 'meja'])
        ->whereDate('tgl_akhir_peremajaan', $tomorrow)
        ->where('status_notif', '!=', 'sent')
        ->get();

    foreach ($peremajaanList as $p) {
        $tanamanNama = $p->semai->tanaman->nama_tanaman ?? 'Tanaman';
        $mejaNama    = $p->meja->meja ?? '-';

        $msg = "🌿 *PENGINGAT H-1 PINDAH MEJA PENDEWASAAN*\n"
             . "━━━━━━━━━━━━━━━━━━\n"
             . "Tanaman  : {$tanamanNama}\n"
             . "Meja     : {$mejaNama}\n"
             . "Tgl Akhir: {$p->tgl_akhir_peremajaan}\n"
             . "Status   : Siap dipindahkan ke meja pendewasaan besok.\n"
             . "━━━━━━━━━━━━━━━━━━\n"
             . "⏱️ " . now()->format('d-m-Y H:i');

        $cnt = $wa->sendToAll($msg);
        if ($cnt > 0) {
            $p->update(['status_notif' => 'sent']);
            $terkirim += $cnt;
        }
    }

    // 3. H-1 PENDEWASAAN (SIAP PANEN)
    $pendewasaanList = Pendewasaan::with(['peremajaan.semai.tanaman', 'meja'])
        ->whereDate('tgl_akhir_pendewasaan', $tomorrow)
        ->where('status_notif', '!=', 'sent')
        ->get();

    foreach ($pendewasaanList as $d) {
        $tanamanNama = $d->peremajaan->semai->tanaman->nama_tanaman ?? 'Tanaman';
        $mejaNama    = $d->meja->meja ?? '-';

        $msg = "🌾 *PENGINGAT H-1 JADWAL PANEN*\n"
             . "━━━━━━━━━━━━━━━━━━\n"
             . "Tanaman  : {$tanamanNama}\n"
             . "Meja     : {$mejaNama}\n"
             . "Tgl Panen: {$d->tgl_akhir_pendewasaan}\n"
             . "Status   : Tanaman siap dipanen besok. Siapkan tempat & timbangan!\n"
             . "━━━━━━━━━━━━━━━━━━\n"
             . "⏱️ " . now()->format('d-m-Y H:i');

        $cnt = $wa->sendToAll($msg);
        if ($cnt > 0) {
            $d->update(['status_notif' => 'sent']);
            $terkirim += $cnt;
        }
    }

    $this->info("Pengingat H-1 selesai diproses. Total pesan terkirim: {$terkirim}");
})->purpose('Cek dan kirim notifikasi WhatsApp pengingat H-1 siklus hidroponik');

// ----------------------------------------------------
// JADWAL OTOMATIS (CRON)
// ----------------------------------------------------
Schedule::command('wa:report')->everySixHours();
Schedule::command('wa:h-minus-1')->dailyAt('07:00');
