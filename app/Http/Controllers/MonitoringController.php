<?php

namespace App\Http\Controllers;

use App\Models\AlertLog;
use App\Models\Monitoring;
use App\Models\SensorThreshold;
use App\Models\WaterControl;
use App\Services\NotifWaService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\StreamedResponse;

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
                'waterTemp' => $data['waterTemp'] ?? null,
                'ph' => $data['ph'] ?? null,
                'tds' => $data['tds'] ?? null,
                'airTemp' => $data['airTemp'] ?? null,
                'humidity' => $data['humidity'] ?? null,
                'water_level' => $data['water_level'] ?? null,
            ]);
        }

        $wc = WaterControl::current();

        // Update status fisik valve jika dikirim ESP32
        if (! empty($data['solenoid_state'])) {
            $valve = strtoupper($data['solenoid_state']) === 'ON' ? 'ON' : 'OFF';
            $wc->update(['valve_state' => $valve]);

            // --- Penjaga mode MANUAL ---------------------------------------
            // Firmware tidak membaca field "mode", jadi aturan "sistem tidak
            // boleh mengisi sendiri" ditegakkan dari sisi server: bila valve
            // terbuka TANPA sesi manual dari pengguna (isi-otomatis jalan lagi
            // setelah alat restart, atau kran manual lupa ditutup > 20 menit),
            // kran ditutup kembali lewat perintah FORCE_STOP.
            if ($wc->mode === 'MANUAL' && $valve === 'ON' && ! $this->sesiManualBaru()) {
                $wc->update(['command' => 'FORCE_STOP']);
                Log::info('[MODE] Mode MANUAL — kran dibuka tanpa perintah pengguna, ditutup paksa.');
            }
        }

        // Ambil command pending untuk ESP32
        $pendingCommand = $wc->command;
        if ($pendingCommand !== 'AUTO') {
            $wc->update(['command' => 'AUTO']);
        }

        // --- Pengingat H-1 jadwal pemindahan meja -------------------------
        // Panel hosting TANPA cron `schedule:run`, jadi pengingat dijalankan
        // dari sini: ESP32 memanggil /api/save-sensor rutin tiap beberapa detik.
        // Dibatasi cache lock 1 jam supaya query-nya jarang jalan, dan
        // disimpan di behind-the-scenes (afterResponse) supaya respons API ke
        // ESP32 TIDAK tertahan oleh proses kirim WhatsApp (bisa sleep 6 detik).
        if (Cache::add('jadwal_h1_lock', 1, 3600)) {
            dispatch(function () {
                try {
                    Artisan::call('jadwal:h1');
                } catch (\Throwable $e) {
                    Log::warning('[WA] jadwal:h1 gagal: '.$e->getMessage());
                }
            })->afterResponse();
        }

        // --- Ringkasan sensor pagi (rata-rata24 jam, jam07:00 WITA) ------
        // Pola sama seperti jadwal:h1 di atas — hosting tanpa cron scheduler.
        if (Cache::add('ringkasan_pagi_lock', 1, 3600)) {
            dispatch(function () {
                try {
                    Artisan::call('sensor:ringkasan');
                } catch (\Throwable $e) {
                    Log::warning('[WA] ringkasan:pagi gagal: '.$e->getMessage());
                }
            })->afterResponse();
        }

        return response()->json([
            'status' => 'ok',
            'command' => $pendingCommand,
            'mode' => $wc->mode,
            'server_time' => now()->toIso8601String(),
        ]);
    }

    // GET /api/latest-sensor — Polling live web
    public function latestSensor()
    {
        $latest = Monitoring::latest('created_at')->first();
        $wc = WaterControl::current();

        $isOnline = false;
        $secondsAgo = null;

        if ($latest && $latest->created_at) {
            $secondsAgo = Carbon::parse($latest->created_at)->diffInSeconds(now());
            $isOnline = $secondsAgo <= 120; // Toleransi 120 detik — sinkron dgn DashboardController
        }

        $anomalies = Monitoring::checkAnomalies($latest);

        return response()->json([
            'is_online' => $isOnline,
            'seconds_ago' => $secondsAgo,
            'anomalies' => $anomalies,
            'sensor' => $latest,
            'water_control' => [
                'mode' => $wc->mode,
                'valve_state' => $wc->valve_state,
                'command' => $wc->command,
                'updated_at' => $wc->updated_at,
            ],
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

        // Sengaja TIDAK kirim WhatsApp di route ini: /monitoring/saveAlert
        // bersifat publik tanpa API key, sehingga bisa disalahpakai untuk
        // membakar kuota/rate-limit nomor WA. Notifikasi WA hanya keluar dari
        // /api/notif-hardware (dilindungi X-API-KEY, hanya firmware).
        return response()->json(['status' => 'ok']);
    }

    // GET /monitoring/chart-data
    public function getChartData()
    {
        $data = Monitoring::latest('created_at')->limit(288)->get()->reverse()->values();

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

    // GET /api/get-thresholds — threshold dari database untuk ESP32
    public function getThresholdsForESP32()
    {
        $thresholds = SensorThreshold::allKeyValue();
        if (empty($thresholds)) {
            $thresholds = [
                'waterTemp' => ['min' => 20.0, 'max' => 35.0],
                'ph' => ['min' => 5.5,  'max' => 6.5],
                'tds' => ['min' => 500.0, 'max' => 1000.0],
                'airTemp' => ['min' => 20.0, 'max' => 40.0],
                'humidity' => ['min' => 50.0, 'max' => 90.0],
            ];
        }

        return response()->json(['status' => true, 'thresholds' => $thresholds, 'fetched_at' => now()]);
    }

    // POST /api/notif-hardware — catat alert dari ESP32 ke AlertLog
    public function receiveFromESP32(Request $request)
    {
        $data = $request->json()->all();
        $type = $data['type'] ?? '';
        $value = $data['value'] ?? '';

        // PERINGATAN: JANGAN pakai empty() — empty("0") === true di PHP,
        // padahal "0" adalah nilai sah (mis. TDS 0). Dulu bikin HTTP 400.
        if ($type === null || trim((string) $type) === '' || $value === null || trim((string) $value) === '') {
            return response()->json(['status' => false, 'msg' => 'type dan value wajib'], 400);
        }

        $cooldown = match ($type) {
            'solenoid' => 60, 'water_level' => 300, default => 600
        };

        if (! AlertLog::bolehKirim($type, $value, $cooldown)) {
            return response()->json(['status' => 'cooldown']);
        }

        AlertLog::simpan($type, $value);
        $this->kirimWa($type, $value);

        return response()->json(['status' => true, 'type' => $type, 'value' => $value]);
    }

    /**
     * Teruskan alert ke WhatsApp (Fonnte — API key diatur di website).
     * Gagal / belum dikonfigurasi -> diam. Tidak boleh memblokir respons API ESP32.
     *
     * Hanya tipe di whitelist (solenoid, water_level Penuh/Kosong, jadwal)
     * yang dikirim. Tipe lain TETAP tercatat ke AlertLog — lihat receiveFromESP32.
     */
    private function kirimWa(string $type, string $value): void
    {
        app(NotifWaService::class)->kirimAlert($type, $value);
    }

    /** Form penarikan data sensor + tabel pratinjau (siap cetak/PDF). */
    public function tarik(Request $request)
    {
        [$dari, $sampai] = $this->rentang($request);

        // Dipaginasi supaya layar tidak dijejali ribuan baris — sama seperti
        // halaman Data Tahap. CSV tetap memuat seluruh hasil (lihat tarikCsv).
        $perPage = (int) $request->get('per_page', 10);
        if (! in_array($perPage, [10, 25, 50, 100], true)) {
            $perPage = 10;
        }
        $data = $this->basisQuery($dari, $sampai)
            ->paginate($perPage)
            ->withQueryString();

        return view('monitoring.tarik', compact('dari', 'sampai', 'data'));
    }

    /** Unduh data sensor sebagai CSV (Excel/LibreOffice) — seluruh rentang. */
    public function tarikCsv(Request $request): StreamedResponse
    {
        [$dari, $sampai] = $this->rentang($request);
        $data = $this->basisQuery($dari, $sampai)->limit(5000)->get();

        return response()->streamDownload(function () use ($data) {
            $out = fopen('php://output', 'w');
            fprintf($out, "\xEF\xBB\xBF");   // BOM supaya Excel membaca UTF-8
            fputcsv($out, ['WAKTU', 'SUHU AIR (C)', 'PH', 'TDS (PPM)', 'SUHU UDARA (C)', 'KELEMBAPAN (%)', 'LEVEL TANDON']);
            foreach ($data as $d) {
                fputcsv($out, [$d->created_at, $d->waterTemp, $d->ph, $d->tds, $d->airTemp, $d->humidity, $d->water_level]);
            }
            fclose($out);
        }, 'data-sensor-'.now()->format('Ymd-His').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /** @return array{0: string, 1: string} [dari, sampai] */
    private function rentang(Request $request): array
    {
        return [
            $request->get('dari', now()->subDays(7)->toDateString()),
            $request->get('sampai', now()->toDateString()),
        ];
    }

    /**
     * Dasar query penarikan sensor. Sengaja TANPA limit: paginate() menghitung
     * total halaman lebih dulu, dan limit yang menempel di builder bisa membuat
     * jumlahnya salah. Batas 5.000 baris dipakai hanya saat ekspor CSV.
     *
     * @return Builder
     */
    private function basisQuery(string $dari, string $sampai)
    {
        return Monitoring::whereDate('created_at', '>=', $dari)
            ->whereDate('created_at', '<=', $sampai)
            ->orderBy('created_at');
    }


    /**
     * Apakah pengguna baru saja membuka kran manual dan belum menutupnya?
     * (maksimal 20 menit — sekaligus batas keamanan kran manual).
     */
    private function sesiManualBaru(): bool
    {
        $terakhir = AlertLog::where('type', 'solenoid')
            ->where('created_at', '>=', now()->subMinutes(20))
            ->latest('id')
            ->first();

        return $terakhir !== null && $terakhir->value === 'ON (manual)';
    }
}
