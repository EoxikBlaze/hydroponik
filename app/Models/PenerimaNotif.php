<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PenerimaNotif extends Model
{
    protected $table      = 'penerima_notifs';
    protected $primaryKey = 'id_penerima_notif';
    protected $fillable   = ['nama', 'no_hp'];

    public function getCleanNumberAttribute(): string
    {
        $no = preg_replace('/[^0-9]/', '', $this->no_hp ?? '');
        if (str_starts_with($no, '0')) {
            $no = '62' . substr($no, 1);
        }
        return $no;
    }

    public static function validNumbers(): array
    {
        return self::all()->map(function ($p) {
            return $p->clean_number;
        })->filter()->values()->toArray();
    }
}
