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
     * Cek status koneksi ESP32 berdasarkan waktu data terakhir
     * (Toleransi 5 menit untuk interval wajar IoT)
     */
    public static function connectionStatus(int $maxMinutes = 5): array
    {
        $last = self::latest('created_at')->first();
        if (! $last || ! $last->created_at) {
            return [
                'online'       => false,
                'last_seen'    => null,
                'diff_minutes' => null,
                'diff_text'    => 'Belum pernah ada data',
                'label'        => 'Offline • Belum Ada Data',
            ];
        }

        $diffMinutes = $last->created_at->diffInMinutes(now());
        $isOnline    = $diffMinutes <= $maxMinutes;

        return [
            'online'       => $isOnline,
            'last_seen'    => $last->created_at,
            'diff_minutes' => $diffMinutes,
            'diff_text'    => $last->created_at->diffForHumans(),
            'label'        => $isOnline ? 'Online • Sinkronisasi Aktif' : 'Offline • Terputus',
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
