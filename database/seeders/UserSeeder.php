<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class UserSeeder extends Seeder {
    public function run(): void {
        User::create([
            'username' => 'admin',
            'email'    => 'admin@politala.ac.id',
            'password' => Hash::make('admin123'), // ganti password sesuai kebutuhan
            'level'    => 'admin',
        ]);
    }
}
