<?php

namespace Database\Seeders;

use App\Models\Tanaman;
use Illuminate\Database\Seeder;

class TanamanSeeder extends Seeder
{
    public function run(): void
    {
        Tanaman::updateOrCreate(['id_tanaman' => 1], ['nama_tanaman' => 'Seledri', 'id_meja' => null]);
        Tanaman::updateOrCreate(['id_tanaman' => 3], ['nama_tanaman' => 'Selada',  'id_meja' => null]);
    }
}
