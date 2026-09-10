<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Siklus extends Model
{
    protected $table      = 'sikluses';
    protected $primaryKey = 'id_siklus';
    protected $fillable   = ['id_tanaman', 'nama_siklus', 'waktu_siklus'];

    public function tanaman() { return $this->belongsTo(Tanaman::class, 'id_tanaman', 'id_tanaman'); }
    public function semai()   { return $this->hasMany(Semai::class, 'id_siklus', 'id_siklus'); }
}
