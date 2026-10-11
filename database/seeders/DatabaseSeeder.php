<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
            DemoUserSeeder::class,
            PetaniSeeder::class,
            MejaSeeder::class,
            TanamanSeeder::class,
            SiklusSeeder::class,
            SemaiSeeder::class,
            PeremajaanSeeder::class,
            PendewasaanSeeder::class,
            PanenSeeder::class,

            SensorThresholdSeeder::class,
            WaterControlSeeder::class,
        ]);
    }
}
