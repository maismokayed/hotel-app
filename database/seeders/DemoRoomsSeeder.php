<?php

namespace Database\Seeders;

use App\Enums\RoomStatus;
use App\Enums\RoomType;
use App\Models\Hotel;
use App\Models\Room;
use Database\Seeders\Support\DemoImageGenerator;
use Illuminate\Database\Seeder;

/**
 * غرف الفنادق التجريبية + صورة لكل غرفة.
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
     * خطة غرف كل فندق: [النوع => [العدد، سعر الليلة]]
     * المفتاح هو نفسه key الفندق في DemoHotelsSeeder.
     */
    public const PLANS = [
        'cham_palace' => [
            ['type' => 'single', 'count' => 6,  'price' => 110],
            ['type' => 'double', 'count' => 8,  'price' => 180],
            ['type' => 'deluxe', 'count' => 4,  'price' => 250],
            ['type' => 'suite',  'count' => 4,  'price' => 320],
        ],
        'dar_yasmine' => [
            ['type' => 'double', 'count' => 4,  'price' => 150],
            ['type' => 'suite',  'count' => 3,  'price' => 240],
        ],
        'shahba_tower' => [
            ['type' => 'single', 'count' => 8,  'price' => 95],
            ['type' => 'double', 'count' => 10, 'price' => 160],
            ['type' => 'deluxe', 'count' => 4,  'price' => 230],
        ],
        'baron_house' => [
            ['type' => 'single', 'count' => 6,  'price' => 45],
            ['type' => 'double', 'count' => 4,  'price' => 70],
        ],
        'afamia_resort' => [
            ['type' => 'double', 'count' => 8,  'price' => 130],
            ['type' => 'deluxe', 'count' => 4,  'price' => 190],
            ['type' => 'suite',  'count' => 4,  'price' => 260],
        ],
        'blue_bay' => [
            ['type' => 'single', 'count' => 3,  'price' => 80],
            ['type' => 'double', 'count' => 6,  'price' => 110],
            ['type' => 'suite',  'count' => 3,  'price' => 200],
        ],
        'orient_homs' => [
            ['type' => 'single', 'count' => 4,  'price' => 60],
            ['type' => 'double', 'count' => 4,  'price' => 90],
        ],
    ];

    public function run(): void
    {
        $hotels    = DemoHotelsSeeder::hotels();
        $created   = 0;
        $withImage = 0;

        foreach (self::PLANS as $hotelKey => $plan) {
            $hotel = $hotels->get($hotelKey);

            if (! $hotel) {
                $this->command?->warn("  ! لم يتم العثور على الفندق: {$hotelKey}");
                continue;
            }

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

                    if ($this->attachImage($room, $hotel, $group['type'])) {
                        $withImage++;
                    }
                }
            }
        }

        $this->command?->info(sprintf(
            '  ✔ غرف: %d غرفة جديدة (المجموع %d)، وصور لـ %d غرفة',
            $created,
            Room::whereIn('hotel_id', $hotels->pluck('id'))->count(),
            $withImage
        ));
    }

    /** الفندق المغلق كل غرفه صيانة، وباقي الفنادق غرفة صيانة كل 9 غرف. */
    private function status(Hotel $hotel, int $sequence): string
    {
        if (! $hotel->is_active) {
            return RoomStatus::MAINTENANCE->value;
        }

        return $sequence % 9 === 0
            ? RoomStatus::MAINTENANCE->value
            : RoomStatus::AVAILABLE->value;
    }

    /**
     * صورة واحدة لكل غرفة (المجموعة singleFile).
     * نولّد صورة واحدة لكل (فندق + نوع غرفة) ونعيد استخدام نفس البايتات
     * لتسريع الـ Seeder.
     */
    private function attachImage(Room $room, Hotel $hotel, string $type): bool
    {
        static $cache = [];

        if ($room->getMedia('images')->isNotEmpty()) {
            return true;
        }

        if (! DemoImageGenerator::available()) {
            return false;
        }

        $cacheKey = $hotel->id . ':' . $type;

        if (! isset($cache[$cacheKey])) {
            $cache[$cacheKey] = DemoImageGenerator::room(
                RoomType::from($type)->label()['en'] . ' Room',
                $hotel->name_en,
                $hotel->id * 1000 + strlen($type) * 7
            );
        }

        if ($cache[$cacheKey] === null) {
            return false;
        }

        $room->addMediaFromString($cache[$cacheKey])
            ->usingFileName('room-' . $hotel->id . '-' . $room->room_number . '.jpg')
            ->usingName($hotel->name_en . ' - Room ' . $room->room_number)
            ->toMediaCollection('images');

        return true;
    }
}
