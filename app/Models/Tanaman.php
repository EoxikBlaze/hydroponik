<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Tanaman extends Model
{
    protected $table      = 'tanamen';
    protected $primaryKey = 'id_tanaman';
    protected $fillable   = ['nama_tanaman', 'id_meja'];

    public function meja()    { return $this->belongsTo(Meja::class, 'id_meja', 'id_meja'); }
    public function siklus()  { return $this->hasMany(Siklus::class, 'id_tanaman', 'id_tanaman'); }
    public function semai()   { return $this->hasMany(Semai::class, 'id_tanaman', 'id_tanaman'); }
}
