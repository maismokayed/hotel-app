<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\User;
use App\Models\Hotel;
use App\Models\Booking;
/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Review>
 */
class ReviewFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id'     => User::factory(),
            'hotel_id'    => Hotel::factory(),
            'booking_id'  => Booking::factory(),
            'comment'     => $this->faker->paragraph(),
            'rating'      => $this->faker->numberBetween(1, 5),
            'review_date' => now()->toDateString(),
        ];
    }
}
