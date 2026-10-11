<?php

namespace App\Http\Controllers;

use App\Models\AlertLog;
use App\Models\Meja;
use App\Models\Monitoring;
use App\Models\Panen;
use App\Models\Pendewasaan;
use App\Models\Peremajaan;
use App\Models\Semai;
use App\Models\Siklus;
use App\Models\Tanaman;
use App\Models\WaterControl;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $latestSensor = Monitoring::latest('created_at')->first();
        $sensorAvg = Monitoring::avg6Jam();
        $waterControl = WaterControl::first();
        $recentAlerts = AlertLog::latest('created_at')->limit(5)->get();

        $totalTanaman = Tanaman::count();
        $totalMeja = Meja::count();
        $totalSiklus = Siklus::count();
        $totalSemai = Semai::count();
        $totalPeremajaan = Peremajaan::count();
        $totalPendewasaan = Pendewasaan::count();
        $totalPanen = Panen::count();
        $totalPanenQty = Panen::sum('panen_berhasil');

        // Batches yang sedang aktif
        $semaiAktif = Semai::where('status', '!=', 'selesai')->count();
        $peremajaanAktif = Peremajaan::count();
        $pendewasaanAktif = Pendewasaan::count();

        // Status Realtime Alat Kebun
        $isOnline = false;
        $secondsAgo = null;
        $diffText = 'Belum pernah ada data';
        if ($latestSensor && $latestSensor->created_at) {
            $carbonDate = Carbon::parse($latestSensor->created_at);
            $secondsAgo = $carbonDate->diffInSeconds(now());
            // Toleransi 120 detik = 4x interval kirim ESP32 (30 detik).
            // Jeda kirim bisa melebar s/d ~70 detik saat notif/timeout menumpuk
            // di loop ESP32 — toleransi lebih kecil bikin badge salah "Mati".
            $isOnline = ($secondsAgo <= 120);
            $diffText = $carbonDate->locale('id')->diffForHumans();
        }
        $iotStatus = [
            'online' => $isOnline,
            'seconds_ago' => $secondsAgo,
            'diff_text' => $diffText,
            'last_seen' => $latestSensor?->created_at,
        ];

        // Data panen terbaru
        $recentPanen = Panen::with(['pendewasaan.peremajaan.semai.tanaman'])->latest('tgl_panen')->limit(5)->get();

        return view('dashboard', [
            'iotStatus' => $iotStatus,
            'total_tanaman' => $totalTanaman,
            'total_meja' => $totalMeja,
            'total_siklus' => $totalSiklus,
            'total_semai' => $totalSemai,
            'total_peremajaan' => $totalPeremajaan,
            'total_pendewasaan' => $totalPendewasaan,
            'total_panen' => $totalPanen,
            'total_panen_qty' => $totalPanenQty,
            'semai_aktif' => $semaiAktif,
            'peremajaan_aktif' => $peremajaanAktif,
            'pendewasaan_aktif' => $pendewasaanAktif,
            'latest_sensor' => $latestSensor,
            'sensor_avg' => $sensorAvg,
            'water_control' => $waterControl,
            'recent_alerts' => $recentAlerts,
            'recent_panen' => $recentPanen,
        ]);
    }
}
