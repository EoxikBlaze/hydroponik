<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Monitoring extends Model
{
    protected $fillable = ['waterTemp', 'ph', 'tds', 'airTemp', 'humidity', 'water_level'];

    /**
     * Cek apakah data ini sama persis dengan record terakhir (anti duplikat)
     */
    public static function isDuplicate(array $data): bool
    {
        $last = self::latest('id')->first();
        if (! $last) return false;

        return $last->waterTemp == ($data['waterTemp'] ?? null)
            && $last->ph        == ($data['ph']        ?? null)
            && $last->tds       == ($data['tds']       ?? null)
            && $last->airTemp   == ($data['airTemp']   ?? null)
            && $last->humidity  == ($data['humidity']  ?? null);
    }

    /**
     * Evaluasi anomali data sensor (mendeteksi sensor lepas, rusak, atau belum dikalibrasi)
     */
    public static function checkAnomalies($row = null): array
    {
        if (! $row) {
            $row = self::latest('created_at')->first();
        }

        if (! $row) {
            return [
                'has_anomaly' => false,
                'count'       => 0,
                'alerts'      => [],
                'status'      => 'no_data',
                'summary'     => 'Belum ada data sensor',
            ];
        }

        $alerts = [];

        // 1. Validasi Suhu Air (DS18B20)
        // Normal: 15°C - 38°C. Anomali jika <= 5, >= 45, atau -127 / 85
        if ($row->waterTemp !== null) {
            $wt = (float)$row->waterTemp;
            if ($wt <= 5.0 || $wt >= 45.0 || $wt == -127.0 || $wt == 85.0) {
                $alerts['waterTemp'] = [
                    'sensor' => 'Suhu Air',
                    'label'  => 'Suhu Air Tidak Wajar (' . $wt . '°C)',
                    'desc'   => 'Suhu ekstrem / kabel terputus. Periksa probe DS18B20.',
                    'value'  => $wt . '°C'
                ];
            }
        }

        // 2. Validasi pH Nutrisi
        // Normal hidroponik: 4.0 - 9.0 (Ideal 5.5 - 6.5).
        // Anomali jika < 3.5 (seperti 2.8 saat pin saturasi 3.3V) atau > 10.0 atau <= 0
        if ($row->ph !== null) {
            $ph = (float)$row->ph;
            if ($ph < 3.5 || $ph > 10.0 || $ph <= 0.0) {
                $alerts['ph'] = [
                    'sensor' => 'pH Nutrisi',
                    'label'  => 'pH Nutrisi Tidak Wajar (' . $ph . ')',
                    'desc'   => 'Nilai pH di luar rentang hidup tanaman (<3.5 atau >10.0). Periksa probe BNC atau putar trimpot kalibrasi modul pH.',
                    'value'  => (string)$ph
                ];
            }
        }

        // 3. Validasi TDS Nutrisi
        // Normal: 100 - 2500 ppm. Anomali jika > 2600 ppm (indikasi konslet/saturasi)
        if ($row->tds !== null) {
            $tds = (float)$row->tds;
            if ($tds > 2600.0) {
                $alerts['tds'] = [
                    'sensor' => 'TDS Nutrisi',
                    'label'  => 'TDS Nutrisi Terlalu Tinggi (' . round($tds) . ' ppm)',
                    'desc'   => 'Nilai TDS melebihi batas jenuh (>2600 ppm). Periksa kemungkinan kabel sinyal konslet ke 3.3V.',
                    'value'  => round($tds) . ' ppm'
                ];
            }
        }

        // 4. Validasi Suhu & Kelembaban Udara (DHT22)
        if ($row->airTemp !== null) {
            $at = (float)$row->airTemp;
            if ($at <= 5.0 || $at >= 55.0) {
                $alerts['airTemp'] = [
                    'sensor' => 'Suhu Udara',
                    'label'  => 'Suhu Udara Tidak Wajar (' . $at . '°C)',
                    'desc'   => 'Suhu udara ekstrem. Periksa posisi sensor DHT22.',
                    'value'  => $at . '°C'
                ];
            }
        }
        if ($row->humidity !== null) {
            $h = (float)$row->humidity;
            if ($h <= 5.0 || $h > 100.0) {
                $alerts['humidity'] = [
                    'sensor' => 'Kelembaban',
                    'label'  => 'Kelembaban Tidak Wajar (' . $h . '%)',
                    'desc'   => 'Nilai kelembaban tidak valid. Periksa sensor DHT22.',
                    'value'  => $h . '%'
                ];
            }
        }

        // 5. Validasi Level Tandon
        if (!empty($row->water_level) && in_array(strtoupper($row->water_level), ['ERROR', 'LOCKOUT'])) {
            $alerts['water_level'] = [
                'sensor' => 'Level Tandon',
                'label'  => 'Konflik Sensor Pelampung (' . $row->water_level . ')',
                'desc'   => 'Float switch mendeteksi posisi terbalik atau sistem lockout. Periksa pelampung air tandon.',
                'value'  => $row->water_level
            ];
        }

        $hasAnomaly = count($alerts) > 0;

        return [
            'has_anomaly' => $hasAnomaly,
            'count'       => count($alerts),
            'alerts'      => $alerts,
            'status'      => $hasAnomaly ? 'periksa_alat' : 'normal',
            'summary'     => $hasAnomaly 
                ? 'Terdeteksi ' . count($alerts) . ' data sensor tidak wajar. Periksa fisik alat di kebun.' 
                : 'Semua sensor terbaca normal.',
        ];
    }

    /**
     * Cek status koneksi ESP32 berdasarkan waktu data terakhir
     */
    public static function connectionStatus(int $maxMinutes = 3): array
    {
        $last = self::latest('created_at')->first();
        if (! $last || ! $last->created_at) {
            return [
                'online'       => false,
                'has_anomaly'  => false,
                'last_seen'    => null,
                'diff_minutes' => null,
                'diff_text'    => 'Belum pernah ada data',
                'label'        => 'Offline • Belum Ada Data',
                'anomalies'    => [],
            ];
        }

        $diffMinutes = $last->created_at->diffInMinutes(now());
        $isOnline    = $diffMinutes <= $maxMinutes;
        $anomalies   = self::checkAnomalies($last);

        $label = 'Offline • Terputus';
        if ($isOnline) {
            $label = $anomalies['has_anomaly'] 
                ? 'Periksa Alat • Data Tidak Wajar' 
                : 'Online • Sinkronisasi Aktif';
        }

        return [
            'online'       => $isOnline,
            'has_anomaly'  => $isOnline && $anomalies['has_anomaly'],
            'last_seen'    => $last->created_at,
            'diff_minutes' => $diffMinutes,
            'diff_text'    => $last->created_at->diffForHumans(),
            'label'        => $label,
            'anomalies'    => $anomalies,
        ];
    }

    /**
     * Rata-rata sensor 6 jam terakhir
     */
    public static function avg6Jam(): ?array
    {
        $rows = self::where('created_at', '>=', now()->subHours(6))->get();
        if ($rows->isEmpty()) return null;

        $fields = ['waterTemp', 'ph', 'tds', 'airTemp', 'humidity'];
        $result = [];
        foreach ($fields as $f) {
            $vals = $rows->whereNotNull($f)->pluck($f);
            $result[$f] = $vals->isNotEmpty() ? round($vals->avg(), 2) : null;
        }
        return $result;
    }
}
