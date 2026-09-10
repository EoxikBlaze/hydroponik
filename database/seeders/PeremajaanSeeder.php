<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Peremajaan;

class PeremajaanSeeder extends Seeder
{
    public function run(): void
    {
        $data = [
            [
                'id_peremajaan' => 1,
                'id_semai' => 15,
                'benih_berhasil' => 5,
                'benih_gagal' => 0,
                'tgl_awal_peremajaan' => '2026-04-02',
                'tgl_akhir_peremajaan' => '2026-04-09',
                'keterangan' => 'ada',
                'status_notif' => 'pending',
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
                'status_notif' => 'pending',
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
                'status_notif' => 'pending',
                'id_meja' => 8,
            ],
            [
                'id_peremajaan' => 4,
                'id_semai' => 15,
                'benih_berhasil' => 12,
                'benih_gagal' => 23,
                'tgl_awal_peremajaan' => '2026-08-04',
                'tgl_akhir_peremajaan' => '2026-08-11',
                'keterangan' => 'wee',
                'status_notif' => 'pending',
                'id_meja' => 8,
            ],
        ];

        foreach ($data as $item) {
            Peremajaan::updateOrCreate(['id_peremajaan' => $item['id_peremajaan']], $item);
        }
    }
}
