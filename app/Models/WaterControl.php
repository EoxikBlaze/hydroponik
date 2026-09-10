<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class WaterControl extends Model
{
    public $timestamps  = false;
    protected $fillable = ['command', 'mode', 'valve_state'];

    public static function current(): self
    {
        return self::first() ?? self::create(['command' => 'AUTO', 'mode' => 'AUTO', 'valve_state' => 'OFF']);
    }
}
