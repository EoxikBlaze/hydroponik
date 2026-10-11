<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/** Akun petani — akses lapangan sederhana (Tanam Saya, sensor, profil). */
class PetaniSeeder extends Seeder
{
    public function run(): void
    {
        User::firstOrCreate(['username' => 'petani'], [
            'username' => 'petani',
            'email' => 'petani@harvesthouse.local',
            'password' => Hash::make('petani123'),
            'level' => 'petani',
        ]);
    }
}
