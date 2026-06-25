<?php

namespace Database\Factories;

use App\Models\City;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class HotelFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => $this->faker->company,
            'city_id' => City::factory(),
            'address' => $this->faker->address,
            'star_rating' => $this->faker->numberBetween(1, 5),
            'is_active' => true,
            'user_id' => User::factory(),
        ];
    }
}
