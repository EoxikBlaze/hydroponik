<?php

namespace Database\Seeders;

use App\Models\WaterControl;
use Illuminate\Database\Seeder;

class WaterControlSeeder extends Seeder
{
    public function run(): void
    {
        WaterControl::firstOrCreate([], ['command' => 'AUTO', 'mode' => 'AUTO']);
    }
}
