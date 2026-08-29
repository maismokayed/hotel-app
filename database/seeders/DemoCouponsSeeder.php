<?php

namespace Database\Seeders;

use App\Models\Coupon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

/**
 * كوبونات الخصم التجريبية: صالحة، منتهية، ومعطّلة.
 *
 * ملاحظة: used_count يبقى 0 هنا، ويزيده DemoBookingsSeeder
 * حسب عدد الحجوزات الفعلية غير الملغاة التي استخدمت الكوبون.
 */
class DemoCouponsSeeder extends Seeder
{
    /** الكوبونات الصالحة التي تستخدمها الحجوزات التجريبية. */
    public const USABLE = ['WELCOME10', 'SUMMER25', 'FLAT50', 'LOYAL5'];

    public function run(): void
    {
        $coupons = [
            [
                'code'           => 'WELCOME10',
                'discount_type'  => 'percentage',
                'discount_value' => 10,
                'max_uses'       => 100,
                'expires_at'     => now()->addMonths(6),
                'is_active'      => true,
            ],
            [
                'code'           => 'SUMMER25',
                'discount_type'  => 'percentage',
                'discount_value' => 25,
                'max_uses'       => 50,
                'expires_at'     => now()->addMonths(2),
                'is_active'      => true,
            ],
            [
                'code'           => 'FLAT50',
                'discount_type'  => 'fixed',
                'discount_value' => 50,
                'max_uses'       => 30,
                'expires_at'     => now()->addMonths(3),
                'is_active'      => true,
            ],
            [
                'code'           => 'LOYAL5',
                'discount_type'  => 'percentage',
                'discount_value' => 5,
                'max_uses'       => null,
                'expires_at'     => null,
                'is_active'      => true,
            ],
            [
                // منتهي الصلاحية
                'code'           => 'SPRING15',
                'discount_type'  => 'percentage',
                'discount_value' => 15,
                'max_uses'       => 40,
                'expires_at'     => now()->subMonth(),
                'is_active'      => true,
            ],
            [
                // معطّل يدوياً
                'code'           => 'OFFSEASON20',
                'discount_type'  => 'fixed',
                'discount_value' => 20,
                'max_uses'       => 25,
                'expires_at'     => now()->addMonths(4),
                'is_active'      => false,
            ],
        ];

        foreach ($coupons as $coupon) {
            Coupon::firstOrCreate(
                ['code' => $coupon['code']],
                $coupon + ['used_count' => 0]
            );
        }

        $this->command?->info(sprintf(
            '  ✔ كوبونات: %d (منها 1 منتهي و 1 معطّل)',
            count($coupons)
        ));
    }

    /** الكوبونات الصالحة للاستخدام في الحجوزات. */
    public static function usable(): Collection
    {
        return Coupon::whereIn('code', self::USABLE)->orderBy('id')->get();
    }
}
