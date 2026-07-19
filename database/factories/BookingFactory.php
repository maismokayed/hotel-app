<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Hotel;
use App\Models\User;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Booking>
 */
class BookingFactory extends Factory
{
    public function definition(): array
    {
        $checkIn  = $this->faker->dateTimeBetween('+1 days', '+10 days');
        $checkOut = $this->faker->dateTimeBetween('+11 days', '+20 days');

        return [
            'user_id'          => User::factory(),
            'hotel_id'         => Hotel::factory(),
            'coupon_id'        => null,
            'check_in_date'    => $checkIn,
            'check_out_date'   => $checkOut,
            'status'           => 'pending',
            'total_price'      => $this->faker->randomFloat(2, 100, 1000),
            'discount_amount'  => 0,
            'final_price'      => $this->faker->randomFloat(2, 100, 1000),
            'number_of_guests' => $this->faker->numberBetween(1, 4),
        ];
    }
}
