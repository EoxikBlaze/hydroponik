<?php

namespace Database\Seeders;

use App\Models\Semai;
use Illuminate\Database\Seeder;

/**
 * Data semai contoh. Angka dibuat konsisten dengan turunannya
 * (total peremajaan dijamin <= benih_berhasil semai induk).
 */
class SemaiSeeder extends Seeder
{
    public function run(): void
    {
        $data = [
            [
                'id_semai' => 15,
                'id_tanaman' => 3,
                'id_siklus' => 3,
                'jumlah_benih' => 200,
                'tgl_awal_semai' => '2026-05-02',
                'tgl_akhir_semai' => '2026-05-07',
                'status' => 'selesai',
                'keterangan' => 'ada',
                'benih_berhasil' => 150,
                'benih_gagal' => 50,
            ],
            [
                'id_semai' => 16,
                'id_tanaman' => 3,
                'id_siklus' => 3,
                'jumlah_benih' => 1000,
                'tgl_awal_semai' => '2026-05-19',
                'tgl_akhir_semai' => '2026-05-24',
                'status' => 'selesai',
                'keterangan' => '',
                'benih_berhasil' => 800,
                'benih_gagal' => 200,
            ],
        ];

        foreach ($data as $item) {
            Semai::updateOrCreate(['id_semai' => $item['id_semai']], $item);
        }
    }
}
