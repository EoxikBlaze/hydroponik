<?php

namespace App\Models;

use App\Models\Concerns\HasDemoExpiry;
use Illuminate\Database\Eloquent\Model;

class Meja extends Model
{
    use HasDemoExpiry;

    protected $table = 'mejas';

    protected $primaryKey = 'id_meja';

    protected $fillable = ['meja', 'jumlah_lubang'];

    public function tanaman()
    {
        return $this->hasMany(Tanaman::class, 'id_meja', 'id_meja');
    }

    public function peremajaan()
    {
        return $this->hasMany(Peremajaan::class, 'id_meja', 'id_meja');
    }

    public function pendewasaan()
    {
        return $this->hasMany(Pendewasaan::class, 'id_meja', 'id_meja');
    }
}
