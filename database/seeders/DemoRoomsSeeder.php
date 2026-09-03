<?php

namespace Database\Seeders;

use App\Enums\RoomStatus;
use App\Models\Hotel;
use App\Models\Room;
use Illuminate\Database\Seeder;

/**
 * غرف الفنادق التجريبية.
 *
 * ما في صور للغرف هون بشكل مقصود (متل الفنادق، تُضاف لاحقاً يدوياً).
 * السنغل والجناح دايماً غرفة واحدة بكل فندق. الدبل والديلوكس بين 2-3
 * حسب تصنيف الفندق بالنجوم، وأربع فنادق "مميزة" (FOCUS_HOTELS) إلها
 * عدد أكبر من غرف الدبل/الديلوكس لتصير عليها حجوزات أكتر لاحقاً.
 *
 * الأسعار بالليرة السورية الجديدة، تقريبية وغير حقيقية، بس متناسبة مع
 * تصنيف كل فندق بالنجوم.
 *
 * ترقيم الغرف حسب الطابق: 101، 102 ... 201، 202 ...
 */
class DemoRoomsSeeder extends Seeder
{
    /** سعة كل نوع غرفة. */
    public const CAPACITY = [
        'single' => 1,
        'double' => 2,
        'deluxe' => 3,
        'suite'  => 4,
    ];

    /**
     * سعر الليلة (ليرة سورية جديدة) لكل نوع غرفة حسب تصنيف الفندق بالنجوم.
     * محسوبة تقريباً على سعر صرف ~135 ل.س جديدة للدولار (يعني 500 ل.س ≈ 3.7$)،
     * بأسعار غرف واقعية بالدولار (سنغل 3 نجوم ~30$ لغاية جناح 5 نجوم ~160$).
     */
    public const PRICES = [
        2 => ['single' => 2800, 'double' => 4200,  'deluxe' => 6000,  'suite' => 9000],
        3 => ['single' => 4000, 'double' => 6000,  'deluxe' => 9000,  'suite' => 13000],
        4 => ['single' => 5200, 'double' => 7800,  'deluxe' => 11400, 'suite' => 16600],
        5 => ['single' => 6800, 'double' => 10300, 'deluxe' => 14900, 'suite' => 21800],
    ];

    /**
     * الفنادق المميزة يلي رح ينكترلها عدد غرف الدبل/الديلوكس.
     * الـ key لازم يطابق key الفندق بـ DemoHotelsSeeder::HOTELS.
     */
    public const FOCUS_HOTELS = [
        'sheraton_aleppo_hotel', // الشيراتون - حلب
        'riga_palace_hotel',     // ريغا بالاس - حلب
        'lamira_resort',         // لاميرا - اللاذقية
        'four_seasons_damascus', // فورسيزون - دمشق
    ];

    public function run(): void
    {
        $hotels  = DemoHotelsSeeder::hotels();
        $created = 0;
        $skipped = 0;

        foreach (DemoHotelsSeeder::HOTELS as $definition) {
            $hotel = $hotels->get($definition['key']);

            if (! $hotel) {
                $this->command?->warn("  ! لم يتم العثور على الفندق: {$definition['key']}");
                $skipped++;
                continue;
            }

            $plan = $this->planFor($hotel, $definition['key']);

            $floor    = 1;
            $onFloor  = 0;
            $sequence = 0;

            foreach ($plan as $group) {
                for ($i = 0; $i < $group['count']; $i++) {
                    if ($onFloor === 10) {
                        $floor++;
                        $onFloor = 0;
                    }

                    $onFloor++;
                    $sequence++;

                    $room = Room::firstOrCreate(
                        [
                            'hotel_id'    => $hotel->id,
                            'room_number' => (string) ($floor * 100 + $onFloor),
                        ],
                        [
                            'type'            => $group['type'],
                            'capacity'        => self::CAPACITY[$group['type']],
                            'price_per_night' => $group['price'],
                            'status'          => $this->status($hotel, $sequence),
                        ]
                    );

                    if ($room->wasRecentlyCreated) {
                        $created++;
                    }
                }
            }
        }

        $this->command?->info(sprintf(
            '  ✔ غرف: %d غرفة جديدة (المجموع %d)%s',
            $created,
            Room::whereIn('hotel_id', $hotels->pluck('id'))->count(),
            $skipped ? ", تم تخطي {$skipped} فندق" : ''
        ));
    }

    /** خطة غرف الفندق: سنغل وجناح دايماً 1، دبل وديلوكس حسب النجوم أو حسب كونه فندق مميز. */
    private function planFor(Hotel $hotel, string $key): array
    {
        $prices  = self::PRICES[$hotel->star_rating] ?? self::PRICES[3];
        $isFocus = in_array($key, self::FOCUS_HOTELS, true);

        if ($isFocus) {
            $doubleCount = 6;
            $deluxeCount = 4;
        } elseif ($hotel->star_rating >= 4) {
            $doubleCount = 3;
            $deluxeCount = 3;
        } else {
            $doubleCount = 2;
            $deluxeCount = 2;
        }

        return [
            ['type' => 'single', 'count' => 1,            'price' => $prices['single']],
            ['type' => 'double', 'count' => $doubleCount, 'price' => $prices['double']],
            ['type' => 'deluxe', 'count' => $deluxeCount, 'price' => $prices['deluxe']],
            ['type' => 'suite',  'count' => 1,            'price' => $prices['suite']],
        ];
    }

    /** الفندق غير المفعّل كل غرفه صيانة، وباقي الفنادق غرفة صيانة كل 9 غرف. */
    private function status(Hotel $hotel, int $sequence): string
    {
        if (! $hotel->is_active) {
            return RoomStatus::MAINTENANCE->value;
        }

        return $sequence % 9 === 0
            ? RoomStatus::MAINTENANCE->value
            : RoomStatus::AVAILABLE->value;
    }
}
