<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder {
    public function run(): void {
        $this->call([
            UserSeeder::class,
            MejaSeeder::class,
            TanamanSeeder::class,
            SiklusSeeder::class,
            SemaiSeeder::class,
            PeremajaanSeeder::class,
            PendewasaanSeeder::class,
            PenerimaNotifSeeder::class,
            SensorThresholdSeeder::class,
            WaterControlSeeder::class,
        ]);
    }
}
