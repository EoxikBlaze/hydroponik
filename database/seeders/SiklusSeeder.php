<?php

namespace Database\Seeders;

use App\Models\Siklus;
use App\Models\Tanaman;
use Illuminate\Database\Seeder;

class SiklusSeeder extends Seeder
{
    public function run(): void
    {
        // JANGAN biarkan tanaman tanpa durasi (dropdown form semai jadi kosong).
        // Setiap tanaman wajib punya 3 tahap; durasi yang sudah ada tidak diubah.
        $tahap = ['Semai' => 7, 'Peremajaan' => 14, 'Pendewasaan' => 21];
        foreach (Tanaman::all() as $t) {
            foreach ($tahap as $nama => $hari) {
                Siklus::firstOrCreate(
                    ['id_tanaman' => $t->id_tanaman, 'nama_siklus' => $nama],
                    ['waktu_siklus' => $hari],
                );
            }
        }
    }
}
