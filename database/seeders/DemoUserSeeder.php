<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoUserSeeder extends Seeder
{
    /**
     * Akun demo untuk presentasi website.
     * Jalankan: php artisan db:seed --class=DemoUserSeeder --no-interaction
     */
    public function run(): void
    {
        User::updateOrCreate(['username' => 'demo'], [
            'username' => 'demo',
            'email' => 'demo@harvesthouse.biz.id',
            'password' => Hash::make('demo123'),
            'level' => 'user',
            'is_demo' => true,
        ]);
    }
}
