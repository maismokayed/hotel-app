<?php

namespace Database\Factories;

use App\Models\Service;
use Illuminate\Database\Eloquent\Factories\Factory;

class ServiceFactory extends Factory
{
    protected $model = Service::class;

    public function definition(): array
    {
        return [
            'name_ar' => fake()->unique()->word(),
            'name_en' => fake()->unique()->word(),
        ];
    }
}
