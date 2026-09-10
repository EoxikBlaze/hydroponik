<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder;
use App\Models\WaterControl;

class WaterControlSeeder extends Seeder {
    public function run(): void {
        WaterControl::create(['command' => 'AUTO', 'mode' => 'AUTO']);
    }
}
