<?php

namespace App\Http\Controllers;

use App\Models\WaterControl;
use App\Services\NotifWaService;
use Illuminate\Http\Request;

class WaterControlController extends Controller
{
    public function index()
    {
        return view('watercontrol.index', ['wc' => WaterControl::current()]);
    }

    public function fill()
    {
        WaterControl::current()->update(['command' => 'FORCE_FILL']);

        // Perintah manual -> catat + kabari WhatsApp (sama seperti auto dari ESP32)
        app(NotifWaService::class)->catatDanKirim('solenoid', 'ON (manual)');

        return back()->with('success', 'Perintah isi air dikirim.');
    }

    public function stop()
    {
        WaterControl::current()->update(['command' => 'FORCE_STOP']);

        app(NotifWaService::class)->catatDanKirim('solenoid', 'OFF (manual)');

        return back()->with('success', 'Pengisian dihentikan.');
    }

    public function status()
    {
        return response()->json(WaterControl::current());
    }

    public function setMode(Request $request)
    {
        $request->validate(['mode' => 'required|in:AUTO,MANUAL']);
        $wc = WaterControl::current();
        $wc->update(['mode' => $request->mode]);

        // Mode MANUAL = sistem tidak boleh mengisi sendiri. FORCE_STOP membuat
        // alat menutup kran sekaligus keluar dari logika isi-otomatisnya.
        if ($request->mode === 'MANUAL') {
            $wc->update(['command' => 'FORCE_STOP']);
        }

        // Form ini dikirim sebagai HTML form biasa (bukan AJAX) — harus redirect
        // kembali ke halaman. Sebelumnya mengembalikan JSON sehingga browser
        // menampilkan teks mentah {"status":true}.
        $label = $request->mode === 'AUTO' ? 'AUTO (otomatis)' : 'MANUAL (pengguna)';

        return back()->with('success', "Mode kerja sistem diubah ke {$label}.");
    }
}
