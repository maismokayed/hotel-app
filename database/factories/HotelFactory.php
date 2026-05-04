<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Hotel;
use App\Models\User;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Hotel>
 */
class HotelFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
              'name' => $this->faker->company,
            'city' => $this->faker->city,
            'address' => $this->faker->address,
            'star_rating' => $this->faker->numberBetween(1, 5),
            'is_active' => true,
            'user_id' => User::factory(), 
        ];
    }
}
