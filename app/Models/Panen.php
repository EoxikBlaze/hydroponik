<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Panen extends Model
{
    protected $table      = 'panens';
    protected $primaryKey = 'id_panen';
    protected $fillable   = [
        'id_pendewasaan', 'panen_berhasil', 'panen_gagal',
        'tgl_panen', 'keterangan', 'status_notif',
    ];
    protected $casts = ['tgl_panen' => 'date'];

    public function pendewasaan() { return $this->belongsTo(Pendewasaan::class, 'id_pendewasaan', 'id_pendewasaan'); }
}
