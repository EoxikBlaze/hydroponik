<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder;
use App\Models\PenerimaNotif;

class PenerimaNotifSeeder extends Seeder {
    public function run(): void {
        $data = [
            ['nama' => 'Adhiyat', 'no_hp' => '82159010106'],
            ['nama' => 'Ezi',     'no_hp' => '82251612837'],
            ['nama' => 'Yoga',    'no_hp' => '85820457330'],
            ['nama' => "Na'im",   'no_hp' => '81528455242'],
            ['nama' => 'Yanda',   'no_hp' => '82213513942'],
        ];
        foreach ($data as $d) PenerimaNotif::create($d);
    }
}
