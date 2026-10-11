<?php

namespace Database\Seeders;

use App\Models\Peremajaan;
use Illuminate\Database\Seeder;

/**
 * Data peremajaan contoh. Konsisten dengan induk & turunannya:
 * - total (berhasil+gagal) per semai  <= benih_berhasil semai
 * - total pendewasaan per peremajaan  <= benih_berhasil peremajaan
 */
class PeremajaanSeeder extends Seeder
{
    public function run(): void
    {
        $data = [
            [
                'id_peremajaan' => 1,
                'id_semai' => 15,
                'benih_berhasil' => 15,
                'benih_gagal' => 5,
                'tgl_awal_peremajaan' => '2026-05-08',
                'tgl_akhir_peremajaan' => '2026-05-15',
                'keterangan' => 'ada',
                'id_meja' => 3,
            ],
            [
                'id_peremajaan' => 2,
                'id_semai' => 15,
                'benih_berhasil' => 5,
                'benih_gagal' => 5,
                'tgl_awal_peremajaan' => '2026-05-18',
                'tgl_akhir_peremajaan' => '2026-05-25',
                'keterangan' => 'T',
                'id_meja' => 3,
            ],
            [
                'id_peremajaan' => 3,
                'id_semai' => 15,
                'benih_berhasil' => 9,
                'benih_gagal' => 10,
                'tgl_awal_peremajaan' => '2026-08-19',
                'tgl_akhir_peremajaan' => '2026-08-26',
                'keterangan' => 'benih caipira',
                'id_meja' => 8,
            ],
            [
                'id_peremajaan' => 4,
                'id_semai' => 15,
                'benih_berhasil' => 46,
                'benih_gagal' => 23,
                'tgl_awal_peremajaan' => '2026-08-04',
                'tgl_akhir_peremajaan' => '2026-08-11',
                'keterangan' => 'wee',
                'id_meja' => 8,
            ],
        ];

        foreach ($data as $item) {
            Peremajaan::updateOrCreate(['id_peremajaan' => $item['id_peremajaan']], $item);
        }
    }
}
