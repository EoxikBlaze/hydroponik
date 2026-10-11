<?php

namespace App\Models;

use App\Models\Concerns\HasDemoExpiry;
use Illuminate\Database\Eloquent\Model;

class Panen extends Model
{
    use HasDemoExpiry;

    protected $table = 'panens';

    protected $primaryKey = 'id_panen';

    protected $fillable = [
        'id_pendewasaan', 'panen_berhasil', 'panen_gagal',
        'tgl_panen', 'keterangan',
    ];

    protected $casts = ['tgl_panen' => 'date'];

    public function pendewasaan()
    {
        return $this->belongsTo(Pendewasaan::class, 'id_pendewasaan', 'id_pendewasaan');
    }
}
