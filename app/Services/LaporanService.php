<?php
namespace App\Services;
use App\Models\Monitoring;
use App\Models\AlertLog;

class LaporanService
{
    public function generate(): string
    {
        $sensor = Monitoring::avg6Jam();
        $alerts = AlertLog::alert6Jam();

        if (! $sensor) return '📊 Tidak ada data sensor dalam 6 jam terakhir.';

        $msg  = "📊 *SMART REPORT HIDROPONIK (6 JAM)*\n";
        $msg .= "━━━━━━━━━━━━━━━━━━\n";
        $msg .= "🌡️ Suhu Air   : {$sensor['waterTemp']} °C\n";
        $msg .= "🧪 pH Air     : {$sensor['ph']}\n";
        $msg .= "⚡ TDS        : {$sensor['tds']} ppm\n";
        $msg .= "🌤️ Suhu Udara: {$sensor['airTemp']} °C\n";
        $msg .= "💧 Kelembapan: {$sensor['humidity']} %\n";
        $msg .= "━━━━━━━━━━━━━━━━━━\n";

        if (! empty($alerts)) {
            $msg .= "⚠️ *ALERT TERDETEKSI:*\n";
            foreach ($alerts as $a) {
                $msg .= "- {$a['type']} : {$a['value']}\n";
            }
        } else {
            $msg .= "✅ Tidak ada alert\n";
        }

        $msg .= "\n⏱️ " . now()->format('d-m-Y H:i');
        return $msg;
    }
}
