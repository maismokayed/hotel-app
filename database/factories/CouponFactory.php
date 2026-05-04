<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Coupon>
 */
class CouponFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code'           => strtoupper($this->faker->unique()->lexify('COUP-????')),
            'discount_type'  => $this->faker->randomElement(['percentage', 'fixed']),
            'discount_value' => $this->faker->randomFloat(2, 5, 50),
            'used_count'     => 0,
            'max_uses'       => $this->faker->optional()->numberBetween(10, 100),
            'expires_at'     => $this->faker->optional()->dateTimeBetween('+1 days', '+30 days'),
            'is_active'      => true,
        ];
    }
}
