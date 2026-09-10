<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Semai extends Model
{
    protected $table      = 'semais';
    protected $primaryKey = 'id_semai';
    protected $fillable   = [
        'id_tanaman', 'id_siklus', 'jumlah_benih',
        'tgl_awal_semai', 'tgl_akhir_semai', 'status',
        'keterangan', 'benih_berhasil', 'benih_gagal', 'status_notif',
    ];

    public function tanaman()    { return $this->belongsTo(Tanaman::class, 'id_tanaman', 'id_tanaman'); }
    public function siklus()     { return $this->belongsTo(Siklus::class, 'id_siklus', 'id_siklus'); }
    public function peremajaan() { return $this->hasMany(Peremajaan::class, 'id_semai', 'id_semai'); }
}
