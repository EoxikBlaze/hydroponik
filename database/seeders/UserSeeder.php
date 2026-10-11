<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::firstOrCreate(['username' => 'admin'], [
            'username' => 'admin',
            'email' => 'admin@politala.ac.id',
            'password' => Hash::make('@adminbersabda123'), // ganti password sesuai kebutuhan
            'level' => 'admin',
        ]);
    }
}
