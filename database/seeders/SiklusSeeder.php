<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder;
use App\Models\Siklus;

class SiklusSeeder extends Seeder {
    public function run(): void {
        $data = [
            ['id_siklus' => 3, 'id_tanaman' => 3, 'nama_siklus' => 'Semai',       'waktu_siklus' => 5],
            ['id_siklus' => 4, 'id_tanaman' => 3, 'nama_siklus' => 'Peremajaan',  'waktu_siklus' => 7],
            ['id_siklus' => 5, 'id_tanaman' => 3, 'nama_siklus' => 'Pendewasaan', 'waktu_siklus' => 8],
        ];
        foreach ($data as $d) Siklus::create($d);
    }
}
