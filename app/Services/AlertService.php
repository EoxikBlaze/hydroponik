<?php
namespace App\Services;
use App\Models\AlertLog;

class AlertService
{
    private static array $pesanTemplate = [];

    public function formatPesan(string $type, string $value, string $customMsg = ''): string
    {
        $waktu = now()->format('d-m-Y H:i:s');

        return match ($type) {
            'water_level' => match ($value) {
                'Kurang' => "⚠️ *PERINGATAN - AIR TANDON*\n\n💧 Status: *AIR HABIS / KURANG*\n🔴 Tandon perlu diisi segera!\n\n⏱️ {$waktu}",
                'Penuh'  => "✅ *INFO - AIR TANDON*\n\n💧 Status: *AIR SUDAH PENUH*\n🟢 Pengisian dihentikan.\n\n⏱️ {$waktu}",
                default  => "ℹ️ *INFO - AIR TANDON*\n\n💧 Status: *AIR {$value}*\n\n⏱️ {$waktu}",
            },
            'solenoid' => match ($value) {
                'ON'  => "🔵 *INFO - SOLENOID*\n\n🚰 Solenoid: *MENYALA (ON)*\n💧 Pengisian air dimulai.\n\n⏱️ {$waktu}",
                'OFF' => "⭕ *INFO - SOLENOID*\n\n🚰 Solenoid: *MATI (OFF)*\n💧 Pengisian air dihentikan.\n\n⏱️ {$waktu}",
                default => "ℹ️ Solenoid: {$value}\n⏱️ {$waktu}",
            },
            'ph'        => "⚠️ *PERINGATAN - pH TIDAK NORMAL*\n\n🧪 Nilai pH: *{$value}*\n🔴 Segera cek nutrisi!\n\n⏱️ {$waktu}",
            'tds'       => "⚠️ *PERINGATAN - TDS TIDAK NORMAL*\n\n⚡ TDS: *{$value} ppm*\n🔴 Konsentrasi nutrisi perlu dicek!\n\n⏱️ {$waktu}",
            'suhu_air', 'waterTemp' => "⚠️ *PERINGATAN - SUHU AIR TIDAK NORMAL*\n\n🌡️ Suhu Air: *{$value} °C*\n🔴 Segera cek sistem!\n\n⏱️ {$waktu}",
            'airTemp'   => "⚠️ *PERINGATAN - SUHU UDARA TIDAK NORMAL*\n\n🌡️ Suhu Udara: *{$value} °C*\n🔴 Cek lingkungan greenhouse!\n\n⏱️ {$waktu}",
            'humidity'  => "⚠️ *PERINGATAN - KELEMBAPAN TIDAK NORMAL*\n\n💧 Kelembapan: *{$value} %*\n🔴 Cek lingkungan greenhouse!\n\n⏱️ {$waktu}",
            'emergency' => "🚨 *EMERGENCY - SISTEM HIDROPONIK*\n\n❌ Kondisi darurat!\n📋 Detail: *{$value}*\n" . ($customMsg ? "ℹ️ {$customMsg}\n" : "") . "🔴 Segera periksa!\n\n⏱️ {$waktu}",
            default     => "ℹ️ *NOTIFIKASI HIDROPONIK*\n\n📋 Tipe: {$type}\n📊 Nilai: {$value}\n" . ($customMsg ? "💬 {$customMsg}\n" : "") . "\n⏱️ {$waktu}",
        };
    }
}
