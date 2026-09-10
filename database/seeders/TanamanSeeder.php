<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder;
use App\Models\Tanaman;

class TanamanSeeder extends Seeder {
    public function run(): void {
        Tanaman::create(['id_tanaman' => 1, 'nama_tanaman' => 'Seledri', 'id_meja' => null]);
        Tanaman::create(['id_tanaman' => 3, 'nama_tanaman' => 'Selada',  'id_meja' => null]);
    }
}
