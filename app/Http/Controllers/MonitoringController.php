<?php
namespace App\Http\Controllers;
use App\Models\{Monitoring, AlertLog, WaterControl};
use Illuminate\Http\Request;
use Carbon\Carbon;

class MonitoringController extends Controller
{
    public function index()
    {
        return view('monitoring.index');
    }

    // POST /api/save-sensor — dipanggil ESP32 via HTTP REST
    public function saveSensor(Request $request)
    {
        $data = $request->json()->all();
        if (empty($data)) {
            return response()->json(['status' => 'error', 'message' => 'No payload'], 400);
        }

        // Simpan jika bukan duplikat persis
        if (! Monitoring::isDuplicate($data)) {
            Monitoring::create([
                'waterTemp'   => $data['waterTemp']   ?? null,
                'ph'          => $data['ph']           ?? null,
                'tds'         => $data['tds']          ?? null,
                'airTemp'     => $data['airTemp']       ?? null,
                'humidity'    => $data['humidity']     ?? null,
                'water_level' => $data['water_level']  ?? null,
            ]);
        }

        $wc = WaterControl::current();

        // Update status fisik valve jika dikirim ESP32
        if (!empty($data['solenoid_state'])) {
            $valve = strtoupper($data['solenoid_state']) === 'ON' ? 'ON' : 'OFF';
            $wc->update(['valve_state' => $valve]);
        }

        // Ambil command pending untuk ESP32
        $pendingCommand = $wc->command;
        if ($pendingCommand !== 'AUTO') {
            $wc->update(['command' => 'AUTO']);
        }

        return response()->json([
            'status'      => 'ok',
            'command'     => $pendingCommand,
            'mode'        => $wc->mode,
            'server_time' => now()->toIso8601String(),
        ]);
    }

    // GET /api/latest-sensor — Polling live web
    public function latestSensor()
    {
        $latest = Monitoring::latest('created_at')->first();
        $wc     = WaterControl::current();

        $isOnline = false;
        $secondsAgo = null;

        if ($latest && $latest->created_at) {
            $secondsAgo = Carbon::parse($latest->created_at)->diffInSeconds(now());
            $isOnline = $secondsAgo <= 60; // Toleransi 60 detik
        }

        $anomalies = Monitoring::checkAnomalies($latest);

        return response()->json([
            'is_online'    => $isOnline,
            'seconds_ago'  => $secondsAgo,
            'anomalies'    => $anomalies,
            'sensor'       => $latest,
            'water_control'=> [
                'mode'        => $wc->mode,
                'valve_state' => $wc->valve_state,
                'command'     => $wc->command,
                'updated_at'  => $wc->updated_at,
            ]
        ]);
    }

    // POST /monitoring/saveAlert
    public function saveAlert(Request $request)
    {
        $data = $request->json()->all();
        if (empty($data['type']) || empty($data['value'])) {
            return response()->json(['status' => 'error'], 400);
        }

        if (! AlertLog::bolehKirim($data['type'], $data['value'], 600)) {
            return response()->json(['status' => 'cooldown']);
        }

        AlertLog::simpan($data['type'], $data['value']);
        return response()->json(['status' => 'ok']);
    }

    // GET /monitoring/chart-data
    public function getChartData()
    {
        $data = Monitoring::latest('created_at')->limit(30)->get()->reverse()->values();
        return response()->json($data);
    }

    // GET /monitoring/getReport6Jam
    public function getReport6Jam()
    {
        return response()->json([
            'sensor' => Monitoring::avg6Jam(),
            'alerts' => AlertLog::alert6Jam(),
        ]);
    }

    // GET /monitoring/sendFullReport
    public function sendFullReport()
    {
        $sensor = Monitoring::avg6Jam();
        if (! $sensor) return response()->json(['msg' => 'no data']);

        return response()->json(['status' => 'ok']);
    }
}
