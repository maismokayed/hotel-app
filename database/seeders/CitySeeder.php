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
            ['name_ar' => 'حلب',       'name_en' => 'Aleppo'],
            ['name_ar' => 'دمشق',      'name_en' => 'Damascus'],
            ['name_ar' => 'حمص',       'name_en' => 'Homs'],
            ['name_ar' => 'حماة',      'name_en' => 'Hama'],
            ['name_ar' => 'اللاذقية',  'name_en' => 'Latakia'],
            ['name_ar' => 'طرطوس',     'name_en' => 'Tartus'],
            ['name_ar' => 'دير الزور', 'name_en' => 'Deir ez-Zor'],
            ['name_ar' => 'الرقة',     'name_en' => 'Raqqa'],
        ];

        foreach ($cities as $city) {
            City::create($city);
        }
    }
}
