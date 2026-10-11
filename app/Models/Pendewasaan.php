<?php

namespace App\Models;

use App\Models\Concerns\HasDemoExpiry;
use Illuminate\Database\Eloquent\Model;

class Pendewasaan extends Model
{
    use HasDemoExpiry;

    protected $table = 'pendewasaans';

    protected $primaryKey = 'id_pendewasaan';

    protected $fillable = [
        'id_peremajaan', 'id_meja', 'jumlah_masuk', 'tanaman_berhasil', 'tanaman_gagal',
        'tgl_awal_pendewasaan', 'tgl_akhir_pendewasaan', 'keterangan',
    ];

    public function peremajaan()
    {
        return $this->belongsTo(Peremajaan::class, 'id_peremajaan', 'id_peremajaan');
    }

    public function meja()
    {
        return $this->belongsTo(Meja::class, 'id_meja', 'id_meja');
    }

    public function panen()
    {
        return $this->hasMany(Panen::class, 'id_pendewasaan', 'id_pendewasaan');
    }
}
