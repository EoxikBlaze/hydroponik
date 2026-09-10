<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Semai;

class SemaiSeeder extends Seeder
{
    public function run(): void
    {
        $data = [
            [
                'id_semai' => 15,
                'id_tanaman' => 3,
                'id_siklus' => 3,
                'jumlah_benih' => 5,
                'tgl_awal_semai' => '2026-05-02',
                'tgl_akhir_semai' => '2026-05-07',
                'status' => 'selesai',
                'keterangan' => 'ada',
                'benih_berhasil' => 4,
                'benih_gagal' => 1,
                'status_notif' => 'sent',
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
                'benih_berhasil' => 80,
                'benih_gagal' => 200,
                'status_notif' => 'pending',
            ],
        ];

        foreach ($data as $item) {
            Semai::updateOrCreate(['id_semai' => $item['id_semai']], $item);
        }
    }
}
