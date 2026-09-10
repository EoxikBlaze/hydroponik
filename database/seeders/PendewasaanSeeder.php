<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Pendewasaan;

class PendewasaanSeeder extends Seeder
{
    public function run(): void
    {
        $data = [
            [
                'id_pendewasaan' => 1,
                'id_peremajaan' => 1,
                'tanaman_berhasil' => 4,
                'tanaman_gagal' => 1,
                'tgl_awal_pendewasaan' => '2026-04-24',
                'tgl_akhir_pendewasaan' => '2026-05-02',
                'keterangan' => 'ada',
                'status_notif' => 'pending',
                'id_meja' => 3,
            ],
            [
                'id_pendewasaan' => 2,
                'id_peremajaan' => 1,
                'tanaman_berhasil' => 5,
                'tanaman_gagal' => 5,
                'tgl_awal_pendewasaan' => '2026-03-12',
                'tgl_akhir_pendewasaan' => '2026-03-20',
                'keterangan' => 'ada',
                'status_notif' => 'pending',
                'id_meja' => 3,
            ],
            [
                'id_pendewasaan' => 3,
                'id_peremajaan' => 4,
                'tanaman_berhasil' => 23,
                'tanaman_gagal' => 23,
                'tgl_awal_pendewasaan' => '2026-08-11',
                'tgl_akhir_pendewasaan' => '2026-08-19',
                'keterangan' => 'wewe',
                'status_notif' => 'pending',
                'id_meja' => 9,
            ],
        ];

        foreach ($data as $item) {
            Pendewasaan::updateOrCreate(['id_pendewasaan' => $item['id_pendewasaan']], $item);
        }
    }
}
