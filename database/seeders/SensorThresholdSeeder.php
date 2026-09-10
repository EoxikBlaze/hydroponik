<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder;
use App\Models\SensorThreshold;

class SensorThresholdSeeder extends Seeder {
    public function run(): void {
        $data = [
            ['sensor' => 'waterTemp', 'min' => 5,  'max' => 40],
            ['sensor' => 'ph',        'min' => 5,  'max' => 7],
            ['sensor' => 'tds',       'min' => 50, 'max' => 1000],
            ['sensor' => 'airTemp',   'min' => 20, 'max' => 40],
            ['sensor' => 'humidity',  'min' => 50, 'max' => 90],
        ];
        foreach ($data as $d) SensorThreshold::create($d);
    }
}
