<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class AlertLog extends Model
{
    protected $fillable = ['type', 'value', 'status'];

    private static array $cooldownMap = [
        'solenoid'     => 60,
        'water_level'  => 300,
        'ph'           => 600,
        'tds'          => 600,
        'suhu_air'     => 600,
        'waterTemp'    => 600,
        'airTemp'      => 600,
        'humidity'     => 600,
        'emergency'    => 900,
        'switch_error' => 900,
        'lockout'      => 1800,
    ];

    public static function bolehKirim(string $type, string $value, ?int $cooldownDetik = null): bool
    {
        $cooldown = $cooldownDetik ?? (self::$cooldownMap[$type] ?? 600);
        $last     = self::where('type', $type)->latest('id')->first();

        if (! $last)                                  return true;
        if ((string)$last->value !== (string)$value)  return true;

        return now()->diffInSeconds($last->created_at) >= $cooldown;
    }

    public static function simpan(string $type, string $value, string $status = 'pending'): self
    {
        return self::create(['type' => $type, 'value' => $value, 'status' => $status]);
    }

    public static function alert6Jam(): array
    {
        return self::where('created_at', '>=', now()->subHours(6))
            ->latest('id')
            ->get()
            ->unique(fn($r) => $r->type . '-' . $r->value)
            ->values()
            ->toArray();
    }
}
