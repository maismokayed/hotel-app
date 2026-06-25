<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\City;

class CitySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $cities = [
            'Aleppo',
            'Damascus',
            'Homs',
            'Hama',
            'Latakia',
            'Tartus',
            'Deir ez-Zor',
            'Raqqa',
        ];
        foreach ($cities as $city) {
            City::create(['name' => $city]);
        }
    }
}
