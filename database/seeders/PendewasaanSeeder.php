<?php

namespace Database\Seeders;

use App\Models\Pendewasaan;
use Illuminate\Database\Seeder;

/**
 * Data pendewasaan contoh. Konsisten dengan induk & turunannya:
 * - total pendewasaan per peremajaan <= benih_berhasil peremajaan
 * - total panen per pendewasaan     <= tanaman_berhasil pendewasaan
 */
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
                'tgl_awal_pendewasaan' => '2026-05-16',
                'tgl_akhir_pendewasaan' => '2026-05-24',
                'keterangan' => 'ada',
                'id_meja' => 3,
            ],
            [
                'id_pendewasaan' => 2,
                'id_peremajaan' => 1,
                'tanaman_berhasil' => 5,
                'tanaman_gagal' => 5,
                'tgl_awal_pendewasaan' => '2026-06-03',
                'tgl_akhir_pendewasaan' => '2026-06-10',
                'keterangan' => 'ada',
                'id_meja' => 3,
            ],
            [
                'id_pendewasaan' => 3,
                'id_peremajaan' => 4,
                'tanaman_berhasil' => 23,
                'tanaman_gagal' => 23,
                'tgl_awal_pendewasaan' => '2026-08-12',
                'tgl_akhir_pendewasaan' => '2026-08-20',
                'keterangan' => 'wewe',
                'id_meja' => 9,
            ],
        ];

        foreach ($data as $item) {
            Pendewasaan::updateOrCreate(['id_pendewasaan' => $item['id_pendewasaan']], $item);
        }
    }
}
