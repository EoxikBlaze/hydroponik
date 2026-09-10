<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class SensorThreshold extends Model
{
    public $timestamps = false;
    protected $fillable = ['sensor', 'min', 'max'];

    public static function allKeyValue(): array
    {
        return self::all()->mapWithKeys(fn($t) => [
            $t->sensor => ['min' => $t->min, 'max' => $t->max]
        ])->toArray();
    }
}
