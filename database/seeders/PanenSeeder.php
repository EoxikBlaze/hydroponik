<?php

namespace Database\Seeders;

use App\Models\Panen;
use Illuminate\Database\Seeder;

/**
 * Data panen contoh. Konsisten dengan induknya:
 * total panen (berhasil+gagal) per pendewasaan <= tanaman_berhasil pendewasaan.
 */
class PanenSeeder extends Seeder
{
    public function run(): void
    {
        $data = [
            [
                'id_panen' => 1,
                'id_pendewasaan' => 1,
                'panen_berhasil' => 2,
                'panen_gagal' => 0,
                'tgl_panen' => '2026-05-25',
                'keterangan' => 'panen perdana',
            ],
            [
                'id_panen' => 2,
                'id_pendewasaan' => 3,
                'panen_berhasil' => 20,
                'panen_gagal' => 3,
                'tgl_panen' => '2026-08-21',
                'keterangan' => 'panen caipira',
            ],
        ];

        foreach ($data as $item) {
            Panen::updateOrCreate(['id_panen' => $item['id_panen']], $item);
        }
    }
}
