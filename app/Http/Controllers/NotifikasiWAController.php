<?php
namespace App\Http\Controllers;
use App\Models\{AlertLog, PenerimaNotif, SensorThreshold};
use App\Services\{WhatsAppService, AlertService, LaporanService};
use Illuminate\Http\Request;

class NotifikasiWAController extends Controller
{
    protected WhatsAppService $wa;
    protected AlertService    $alertService;

    public function __construct()
    {
        $this->wa           = new WhatsAppService();
        $this->alertService = new AlertService();
    }

    // GET /notifikasi-wa (halaman web)
    public function index()
    {
        return view('notifikasi-wa.index');
    }

    // GET /api/get-thresholds?api_key=HARVEST123
    public function getThresholdsForESP32()
    {
        $thresholds = SensorThreshold::allKeyValue();
        if (empty($thresholds)) {
            $thresholds = [
                'waterTemp' => ['min' => 20.0, 'max' => 35.0],
                'ph'        => ['min' => 5.5,  'max' => 6.5],
                'tds'       => ['min' => 500.0,'max' => 1000.0],
                'airTemp'   => ['min' => 20.0, 'max' => 40.0],
                'humidity'  => ['min' => 50.0, 'max' => 90.0],
            ];
        }
        return response()->json(['status' => true, 'thresholds' => $thresholds, 'fetched_at' => now()]);
    }

    // POST /api/notif-hardware
    public function receiveFromESP32(Request $request)
    {
        $data  = $request->json()->all();
        $type  = $data['type']    ?? '';
        $value = $data['value']   ?? '';
        $msg   = $data['message'] ?? '';

        if (empty($type) || empty($value)) {
            return response()->json(['status' => false, 'msg' => 'type dan value wajib'], 400);
        }

        $cooldown = match($type) { 'solenoid' => 60, 'water_level' => 300, default => 600 };

        if (! AlertLog::bolehKirim($type, $value, $cooldown)) {
            return response()->json(['status' => 'cooldown']);
        }

        $message  = $this->alertService->formatPesan($type, $value, $msg);
        $alert    = AlertLog::simpan($type, $value);
        $berhasil = $this->wa->sendToAll($message);
        if ($berhasil > 0) $alert->update(['status' => 'sent']);

        return response()->json(['status' => true, 'msg' => "Terkirim ke {$berhasil} penerima", 'type' => $type, 'value' => $value]);
    }

    // GET /notifikasi-wa/getThresholds
    public function getThresholds()
    {
        return response()->json(SensorThreshold::allKeyValue());
    }

    // GET /notifikasi-wa/send-report
    public function sendReport()
    {
        $message  = (new LaporanService())->generate();
        $berhasil = $this->wa->sendToAll($message);
        return response()->json(['status' => true, 'kirim_ke' => $berhasil, 'message' => $message]);
    }

    // GET /notifikasi-wa/test
    public function test()
    {
        $message  = "🌿 *TEST WA - HarvestHouse*\nSistem berfungsi normal.\n⏱️ " . now()->format('d-m-Y H:i:s');
        $berhasil = $this->wa->sendToAll($message);
        return response()->json(['status' => true, 'kirim_ke' => $berhasil]);
    }
}
