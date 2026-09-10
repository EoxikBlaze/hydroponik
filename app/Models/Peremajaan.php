<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Peremajaan extends Model
{
    protected $table      = 'peremajaans';
    protected $primaryKey = 'id_peremajaan';
    protected $fillable   = [
        'id_semai', 'id_meja', 'benih_berhasil', 'benih_gagal',
        'tgl_awal_peremajaan', 'tgl_akhir_peremajaan', 'keterangan', 'status_notif',
    ];

    public function semai()       { return $this->belongsTo(Semai::class, 'id_semai', 'id_semai'); }
    public function meja()        { return $this->belongsTo(Meja::class, 'id_meja', 'id_meja'); }
    public function pendewasaan() { return $this->hasMany(Pendewasaan::class, 'id_peremajaan', 'id_peremajaan'); }
}
