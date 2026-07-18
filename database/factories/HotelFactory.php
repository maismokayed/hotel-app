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
            'name_ar' => $this->faker->company,
            'name_en' => $this->faker->company,

            'description_ar' => $this->faker->paragraph,
            'description_en' => $this->faker->paragraph,

            'city_id' => City::factory(),

            'address_ar' => $this->faker->address,
            'address_en' => $this->faker->address,

            'phone' => $this->faker->phoneNumber,
            'email' => $this->faker->unique()->companyEmail,
            'star_rating' => $this->faker->numberBetween(1, 5),
            'is_active' => true,
            'user_id' => User::factory(),
        ];
    }
}
