<?php

namespace Database\Seeders;

use App\Models\AlertLog;
use App\Models\Monitoring;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

/**
 * Data demo sensor palsu yang realistis: hari ini, jam 08.00-15.00 WITA.
 *
 * Skenario satu hari kebun:
 *  - Suhu udara naik ke puncak ±33,5°C siang, kelembaban turun ke ±57%
 *  - Suhu air tandon naik lambat 24,6 -> 27,2°C lalu landai
 *  - TDS perlahan turun (nutrisi diserap), tandon menipis -> pengisian otomatis
 *    (TDS terencer), lalu nutrisi ditambah (TDS naik lagi)
 *  - pH stabil di kisaran 6,0 (random walk)
 *
 * Jalankan: php artisan db:seed --class=MonitoringDemoSeeder --no-interaction
 * PERHATIAN: menghapus SEMUA data monitorings & alert_logs yang ada!
 */
class MonitoringDemoSeeder extends Seeder
{
    private const TZ_WITA = 'Asia/Makassar';

    /** Anchor kurva harian [jam desimal => nilai] — diinterpolasi linier + noise */
    private const AIR_TEMP = [8 => 27.4, 9 => 29.0, 10 => 30.6, 11 => 31.9, 12 => 32.9, 13 => 33.5, 14 => 33.2, 15 => 32.2];

    private const HUMIDITY = [8 => 76.0, 9 => 72.0, 10 => 68.0, 11 => 64.0, 12 => 60.5, 13 => 57.5, 14 => 57.0, 15 => 59.5];

    private const WATER_TEMP = [8 => 24.6, 10 => 25.8, 12 => 26.8, 13 => 27.2, 15 => 26.9];

    private const TDS = [8 => 830, 10.67 => 762, 11.17 => 738, 11.42 => 662, 11.50 => 672, 11.83 => 785, 13 => 772, 14.67 => 752, 14.83 => 706, 15 => 700];

    /** Cerita level tandon: [jam mulai, jam selesai, status] */
    private const WATER_LEVEL = [
        [8.0,   10.66, 'Sedang'],
        [10.67, 11.29, 'Rendah'],   // 10.40 air menipis
        [11.30, 11.41, 'Sedang'],   // 11.18 pengisian berlangsung
        [11.42, 13.49, 'Penuh'],    // 11.25 pengisian selesai
        [13.50, 14.74, 'Sedang'],
        [14.75, 14.82, 'Rendah'],   // 14.45 air menipis lagi
        [14.83, 14.99, 'Sedang'],   // 14.50 pengisian kedua
        [15.00, 15.00, 'Penuh'],
    ];

    /** Kejadian alert (jam desimal WITA, tipe, nilai) */
    private const ALERTS = [
        [10.67, 'water_level', 'Air Habis / Kritis'],
        [11.17, 'solenoid',    'Pengisian Air Menyala (ON)'],
        [11.42, 'solenoid',    'Pengisian Air Berhenti (OFF)'],
        [14.75, 'water_level', 'Air Habis / Kritis'],
        [14.83, 'solenoid',    'Pengisian Air Menyala (ON)'],
        [15.00, 'solenoid',    'Pengisian Air Berhenti (OFF)'],
    ];

    public function run(): void
    {
        mt_srand(20261001); // RNG tetap — hasil sama setiap kali dijalankan

        Monitoring::query()->delete();
        AlertLog::query()->delete();

        $ph = 6.0;
        for ($i = 0; $i <= 84; $i++) { // 08.00-15.00 WITA, interval 5 menit = 85 titik
            $jam = 8.0 + $i * 5 / 60;
            $waktu = Carbon::today(self::TZ_WITA)->setTime(8, 0)->addMinutes($i * 5);

            // pH: random walk mean-reversion di sekitar 6,0 + efek kejadian
            $ph += (6.05 - $ph) * 0.08 + $this->noise(0.04);
            if ($jam >= 11.42 && $jam < 11.9) {
                $ph += 0.02;
            }  // air baru -> pH naik sedikit
            if ($jam >= 11.83 && $jam < 12.3) {
                $ph -= 0.015;
            } // nutrisi baru -> pH turun sedikit

            $row = new Monitoring([
                'waterTemp' => round($this->interp(self::WATER_TEMP, $jam) + $this->noise(0.12), 1),
                'ph' => round($ph, 1),
                'tds' => round($this->interp(self::TDS, $jam) + $this->noise(4.0), 1),
                'airTemp' => round($this->interp(self::AIR_TEMP, $jam) + $this->noise(0.22), 1),
                'humidity' => round($this->interp(self::HUMIDITY, $jam) + $this->noise(1.1), 1),
                'water_level' => $this->levelPada($jam),
            ]);
            $row->created_at = $waktu->utc();
            $row->save();
        }

        foreach (self::ALERTS as [$jam, $tipe, $nilai]) {
            $alert = AlertLog::simpan($tipe, $nilai);
            $alert->created_at = Carbon::today(self::TZ_WITA)
                ->setTime((int) $jam, (int) round(fmod($jam, 1) * 60))->utc();
            $alert->save();
        }

        $this->command->info('Data demo sensor 08.00-15.00 WITA dibuat: 85 baris monitorings + 6 alert_logs.');
    }

    /** Interpolasi linier antar anchor */
    private function interp(array $anchors, float $x): float
    {
        $keys = array_keys($anchors);
        if ($x <= $keys[0]) {
            return $anchors[$keys[0]];
        }
        if ($x >= end($keys)) {
            return $anchors[end($keys)];
        }
        for ($i = 0; $i < count($keys) - 1; $i++) {
            $a = $keys[$i];
            $b = $keys[$i + 1];
            if ($x >= $a && $x <= $b) {
                return $anchors[$a] + ($anchors[$b] - $anchors[$a]) * (($x - $a) / ($b - $a));
            }
        }

        return $anchors[$keys[0]];
    }

    /** Noise acak simetris dalam ±amp */
    private function noise(float $amp): float
    {
        return (mt_rand() / mt_getrandmax() * 2 - 1) * $amp;
    }

    private function levelPada(float $jam): string
    {
        foreach (self::WATER_LEVEL as [$dari, $sampai, $status]) {
            if ($jam >= $dari && $jam <= $sampai) {
                return $status;
            }
        }

        return 'Sedang';
    }
}
