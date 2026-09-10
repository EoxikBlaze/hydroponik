<?php
namespace App\Http\Controllers;
use App\Models\{Monitoring, AlertLog};
use Illuminate\Http\Request;

class MonitoringController extends Controller
{
    public function index()
    {
        return view('monitoring.index');
    }

    // POST /api/save-sensor  — dipanggil ESP32
    public function saveSensor(Request $request)
    {
        $data = $request->json()->all();
        if (empty($data)) {
            return response()->json(['status' => 'error'], 400);
        }

        if (Monitoring::isDuplicate($data)) {
            return response()->json(['status' => 'skip']);
        }

        Monitoring::create([
            'waterTemp'   => $data['waterTemp']   ?? null,
            'ph'          => $data['ph']           ?? null,
            'tds'         => $data['tds']          ?? null,
            'airTemp'     => $data['airTemp']       ?? null,
            'humidity'    => $data['humidity']     ?? null,
            'water_level' => $data['water_level']  ?? null,
        ]);

        return response()->json(['status' => 'ok']);
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

        $laporan  = new \App\Services\LaporanService();
        $message  = $laporan->generate();
        $wa       = new \App\Services\WhatsAppService();
        $berhasil = $wa->sendToAll($message);

        return response()->json(['status' => 'sent', 'kirim_ke' => $berhasil, 'message' => $message]);
    }
}
