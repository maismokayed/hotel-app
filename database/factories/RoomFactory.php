<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Hotel;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Room>
 */
class RoomFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
           'hotel_id'        => Hotel::factory(),
        'room_number'     => $this->faker->unique()->numberBetween(100, 999),
        'type'            => $this->faker->randomElement(['single', 'double', 'suite']),
        'price_per_night' => $this->faker->randomFloat(2, 50, 500),
        'status'          => 'available',
        'capacity'        => $this->faker->numberBetween(1, 4),
        ];
    }
}
