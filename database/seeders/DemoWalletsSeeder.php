<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use Illuminate\Database\Seeder;

/**
 * محفظة لكل مستخدم تجريبي + عمليات الإيداع الأولية.
 *
 * الرصيد دائماً = مجموع الإيداعات (credit) ناقص السحوبات (debit)،
 * وهذا الثبات يحافظ عليه DemoBookingsSeeder عند الدفع من المحفظة.
 */
class DemoWalletsSeeder extends Seeder
{
    public function run(): void
    {
        $deposits = 0;

        // المدراء والأدمن: محفظة فارغة (لا يحجزون).
        foreach (DemoUsersSeeder::all() as $user) {
            $this->wallet($user);
        }

        foreach (DemoUsersSeeder::normalUsers()->values() as $index => $user) {
            $wallet = $this->wallet($user);

            if ($wallet->transactions()->exists()) {
                continue;
            }

            // إيداعات أولية قبل أقدم حجز، حتى يبقى الرصيد منطقياً زمنياً
            // (لا يوجد دفع من رصيد لم يُودَع بعد).
            $plan = [
                ['amount' => 4000 + ($index % 6) * 600, 'at' => now()->subMonths(13)->setTime(11, 15)],
            ];

            if ($index % 2 === 0) {
                $plan[] = ['amount' => 1500 + ($index % 4) * 500, 'at' => now()->subMonths(12)->setTime(9, 40)];
            }

            foreach ($plan as $deposit) {
                $this->deposit($wallet, $deposit['amount'], $deposit['at']);
                $deposits++;
            }
        }

        $this->command?->info(sprintf(
            '  ✔ محافظ: %d محفظة، %d عملية إيداع، إجمالي الأرصدة %s',
            DemoUsersSeeder::all()->count(),
            $deposits,
            number_format((float) Wallet::whereIn('user_id', DemoUsersSeeder::all()->pluck('id'))->sum('balance'), 2)
        ));
    }

    private function wallet(User $user): Wallet
    {
        return Wallet::firstOrCreate(['user_id' => $user->id], ['balance' => 0]);
    }

    /** إيداع: يزيد الرصيد ويسجّل حركة credit بتاريخ محدد. */
    private function deposit(Wallet $wallet, float $amount, \DateTimeInterface $at): void
    {
        $wallet->increment('balance', $amount);

        $transaction = WalletTransaction::create([
            'wallet_id'        => $wallet->id,
            'user_id'          => $wallet->user_id,
            'amount'           => $amount,
            'transaction_type' => 'credit',
            'reason'           => 'deposit',
            'transaction_date' => $at,
        ]);

        $transaction->forceFill(['created_at' => $at, 'updated_at' => $at])->saveQuietly();
    }
}
