<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Wallet;
use App\Models\User;
/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\WalletTransaction>
 */
class WalletTransactionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'wallet_id'        => Wallet::factory(),
            'user_id'          => User::factory(),
            'amount'           => $this->faker->randomFloat(2, 10, 500),
            'transaction_type' => $this->faker->randomElement(['credit', 'debit']),
            'transaction_date' => now(),
        ];
    }
}
