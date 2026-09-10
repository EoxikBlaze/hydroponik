<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder;
use App\Models\Meja;

class MejaSeeder extends Seeder {
    public function run(): void {
        $data = [
            ['id_meja' => 3,  'meja' => 'Meja Selada',    'jumlah_lubang' => 10],
            ['id_meja' => 8,  'meja' => 'Peremajaan 1',   'jumlah_lubang' => 624],
            ['id_meja' => 9,  'meja' => 'Peremajaan 2',   'jumlah_lubang' => 312],
            ['id_meja' => 10, 'meja' => 'Pendewasaan 1',  'jumlah_lubang' => 200],
            ['id_meja' => 11, 'meja' => 'Pendewasaan 2',  'jumlah_lubang' => 200],
        ];
        foreach ($data as $d) Meja::create($d);
    }
}
