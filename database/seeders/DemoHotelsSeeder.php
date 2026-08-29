<?php

namespace Database\Seeders;

use App\Models\City;
use App\Models\Hotel;
use App\Models\Service;
use Database\Seeders\Support\DemoImageGenerator;
use Illuminate\Database\Seeder;

/**
 * فنادق البيانات التجريبية + الخدمات المرتبطة بها + صور لكل فندق.
 */
class DemoHotelsSeeder extends Seeder
{
    /** الخدمات المتاحة في النظام (تُنشأ إن لم تكن موجودة). */
    public const SERVICES = [
        'wifi'       => ['name_ar' => 'واي فاي مجاني',      'name_en' => 'Free Wi-Fi'],
        'parking'    => ['name_ar' => 'موقف سيارات',        'name_en' => 'Parking'],
        'pool'       => ['name_ar' => 'مسبح',               'name_en' => 'Swimming Pool'],
        'gym'        => ['name_ar' => 'صالة رياضية',        'name_en' => 'Gym'],
        'restaurant' => ['name_ar' => 'مطعم',               'name_en' => 'Restaurant'],
        'room_serv'  => ['name_ar' => 'خدمة الغرف',         'name_en' => 'Room Service'],
        'spa'        => ['name_ar' => 'سبا',                'name_en' => 'Spa'],
        'ac'         => ['name_ar' => 'تكييف',              'name_en' => 'Air Conditioning'],
        'shuttle'    => ['name_ar' => 'نقل من وإلى المطار', 'name_en' => 'Airport Shuttle'],
        'breakfast'  => ['name_ar' => 'إفطار مجاني',        'name_en' => 'Free Breakfast'],
    ];

    /**
     * الفنادق التجريبية.
     * manager: الـ key من DemoUsersSeeder::MANAGERS
     */
    public const HOTELS = [
        [
            'key'            => 'cham_palace',
            'manager'        => 'manager_sham',
            'city'           => 'Damascus',
            'name_ar'        => 'فندق الشام الكبير',
            'name_en'        => 'Grand Cham Damascus',
            'description_ar' => 'فندق خمس نجوم في قلب دمشق، يجمع بين الطابع الشامي الأصيل والخدمات الفندقية الحديثة، ويبعد دقائق عن المدينة القديمة.',
            'description_en' => 'A five-star hotel in the heart of Damascus, blending authentic Damascene character with modern hospitality, minutes away from the Old City.',
            'address_ar'     => 'شارع المتنبي، أبو رمانة، دمشق',
            'address_en'     => 'Al-Mutanabbi St., Abu Rummaneh, Damascus',
            'phone'          => '0113330011',
            'email'          => 'info@grandcham.demo.test',
            'star_rating'    => 5,
            'is_active'      => true,
            'services'       => ['wifi', 'parking', 'pool', 'gym', 'restaurant', 'room_serv', 'spa', 'ac', 'breakfast'],
            'images'         => ['Facade', 'Lobby', 'Pool'],
        ],
        [
            'key'            => 'dar_yasmine',
            'manager'        => 'manager_sham',
            'city'           => 'Damascus',
            'name_ar'        => 'دار الياسمين',
            'name_en'        => 'Dar Al Yasmine Boutique',
            'description_ar' => 'بيت دمشقي تقليدي تم تحويله إلى فندق بوتيك هادئ، بفناء داخلي وبحرة ماء وغرف محدودة العدد.',
            'description_en' => 'A traditional Damascene house turned into a quiet boutique hotel, with an inner courtyard, a fountain and a limited number of rooms.',
            'address_ar'     => 'حارة الزيتون، باب توما، دمشق القديمة',
            'address_en'     => 'Az-Zaytoun Alley, Bab Touma, Old Damascus',
            'phone'          => '0115420088',
            'email'          => 'stay@daryasmine.demo.test',
            'star_rating'    => 4,
            'is_active'      => true,
            'services'       => ['wifi', 'restaurant', 'ac', 'breakfast', 'room_serv'],
            'images'         => ['Courtyard', 'Suite'],
        ],
        [
            'key'            => 'shahba_tower',
            'manager'        => 'manager_halab',
            'city'           => 'Aleppo',
            'name_ar'        => 'برج الشهباء',
            'name_en'        => 'Shahba Tower Aleppo',
            'description_ar' => 'فندق أعمال حديث في حلب الجديدة، يضم قاعات اجتماعات ومركز أعمال وإطلالة على المدينة.',
            'description_en' => 'A modern business hotel in New Aleppo, featuring meeting halls, a business center and city views.',
            'address_ar'     => 'شارع الجامعة، حلب الجديدة، حلب',
            'address_en'     => 'University St., New Aleppo, Aleppo',
            'phone'          => '0212220033',
            'email'          => 'contact@shahbatower.demo.test',
            'star_rating'    => 5,
            'is_active'      => true,
            'services'       => ['wifi', 'parking', 'gym', 'restaurant', 'room_serv', 'ac', 'shuttle', 'breakfast'],
            'images'         => ['Tower', 'Room', 'Restaurant'],
        ],
        [
            'key'            => 'baron_house',
            'manager'        => 'manager_halab',
            'city'           => 'Aleppo',
            'name_ar'        => 'نزل البارون',
            'name_en'        => 'Baron House Aleppo',
            'description_ar' => 'نزل اقتصادي بموقع ممتاز قرب وسط المدينة، مناسب للإقامات القصيرة وللمسافرين منفردين.',
            'description_en' => 'An affordable inn with an excellent location near the city center, suited for short stays and solo travellers.',
            'address_ar'     => 'شارع بارون، العزيزية، حلب',
            'address_en'     => 'Baron St., Aziziyah, Aleppo',
            'phone'          => '0212115577',
            'email'          => 'hello@baronhouse.demo.test',
            'star_rating'    => 3,
            'is_active'      => true,
            'services'       => ['wifi', 'ac', 'breakfast'],
            'images'         => ['Entrance'],
        ],
        [
            'key'            => 'afamia_resort',
            'manager'        => 'manager_sahel',
            'city'           => 'Latakia',
            'name_ar'        => 'منتجع أفاميا الساحلي',
            'name_en'        => 'Afamia Coast Resort',
            'description_ar' => 'منتجع على شاطئ اللاذقية مع مسبحين ومنطقة ألعاب للأطفال ومطعم بحري مطل على البحر.',
            'description_en' => 'A beachfront resort in Latakia with two pools, a kids play area and a seafood restaurant overlooking the sea.',
            'address_ar'     => 'طريق الشاطئ الأزرق، اللاذقية',
            'address_en'     => 'Blue Beach Road, Latakia',
            'phone'          => '0414440022',
            'email'          => 'booking@afamiaresort.demo.test',
            'star_rating'    => 4,
            'is_active'      => true,
            'services'       => ['wifi', 'parking', 'pool', 'restaurant', 'ac', 'breakfast', 'spa'],
            'images'         => ['Beach', 'Pool', 'Bungalow'],
        ],
        [
            'key'            => 'blue_bay',
            'manager'        => 'manager_sahel',
            'city'           => 'Tartus',
            'name_ar'        => 'فندق الخليج الأزرق',
            'name_en'        => 'Blue Bay Tartus',
            'description_ar' => 'فندق عائلي في طرطوس على بعد خطوات من الكورنيش، بغرف واسعة وأجنحة عائلية.',
            'description_en' => 'A family hotel in Tartus, steps away from the corniche, with spacious rooms and family suites.',
            'address_ar'     => 'شارع الكورنيش البحري، طرطوس',
            'address_en'     => 'Sea Corniche St., Tartus',
            'phone'          => '0433310099',
            'email'          => 'info@bluebay.demo.test',
            'star_rating'    => 4,
            'is_active'      => true,
            'services'       => ['wifi', 'parking', 'restaurant', 'ac', 'room_serv'],
            'images'         => ['Seaview', 'Lobby'],
        ],
        [
            'key'            => 'orient_homs',
            'manager'        => 'manager_wasat',
            'city'           => 'Homs',
            'name_ar'        => 'قصر الأورينت',
            'name_en'        => 'Orient Palace Homs',
            'description_ar' => 'فندق في وسط حمص مغلق حالياً بسبب أعمال الصيانة والتجديد.',
            'description_en' => 'A hotel in central Homs, currently closed for maintenance and renovation works.',
            'address_ar'     => 'شارع الحضارة، حمص',
            'address_en'     => 'Al-Hadara St., Homs',
            'phone'          => '0312220044',
            'email'          => 'info@orienthoms.demo.test',
            'star_rating'    => 3,
            'is_active'      => false,
            'services'       => ['wifi', 'parking', 'ac'],
            'images'         => ['Facade'],
        ],
    ];

    public function run(): void
    {
        $managers = DemoUsersSeeder::managers();
        $services = $this->services();

        $withImages = 0;

        foreach (self::HOTELS as $index => $definition) {
            $manager = $managers->get($definition['manager']);

            if (! $manager) {
                $this->command?->warn("  ! لم يتم العثور على مدير الفندق: {$definition['manager']}");
                continue;
            }

            $hotel = Hotel::firstOrCreate(
                ['name_en' => $definition['name_en']],
                [
                    'name_ar'        => $definition['name_ar'],
                    'description_ar' => $definition['description_ar'],
                    'description_en' => $definition['description_en'],
                    'city_id'        => $this->city($definition['city'])->id,
                    'address_ar'     => $definition['address_ar'],
                    'address_en'     => $definition['address_en'],
                    'phone'          => $definition['phone'],
                    'email'          => $definition['email'],
                    'facebook_url'   => 'https://facebook.com/' . $definition['key'],
                    'instagram_url'  => 'https://instagram.com/' . $definition['key'],
                    'star_rating'    => $definition['star_rating'],
                    'is_active'      => $definition['is_active'],
                    'user_id'        => $manager->id,
                ]
            );

            $hotel->services()->syncWithoutDetaching(
                collect($definition['services'])->map(fn ($key) => $services[$key]->id)->all()
            );

            if ($this->attachImages($hotel, $definition, $index)) {
                $withImages++;
            }
        }

        $this->command?->info(sprintf(
            '  ✔ فنادق: %d (منها %d غير مفعّل) + %d خدمة، وصور لـ %d فندق',
            count(self::HOTELS),
            collect(self::HOTELS)->where('is_active', false)->count(),
            count(self::SERVICES),
            $withImages
        ));
    }

    /** إنشاء/جلب الخدمات مفهرسة بالـ key. */
    private function services(): array
    {
        $services = [];

        foreach (self::SERVICES as $key => $service) {
            $services[$key] = Service::firstOrCreate(
                ['name_en' => $service['name_en']],
                ['name_ar' => $service['name_ar']]
            );
        }

        return $services;
    }

    /** جلب المدينة بالاسم الإنكليزي، وإنشاؤها إن لم تكن موجودة. */
    private function city(string $nameEn): City
    {
        $arabicNames = [
            'Damascus' => 'دمشق',
            'Aleppo'   => 'حلب',
            'Homs'     => 'حمص',
            'Hama'     => 'حماة',
            'Latakia'  => 'اللاذقية',
            'Tartus'   => 'طرطوس',
        ];

        return City::firstOrCreate(
            ['name_en' => $nameEn],
            ['name_ar' => $arabicNames[$nameEn] ?? $nameEn]
        );
    }

    /** توليد صور الفندق وإضافتها لمجموعة images. */
    private function attachImages(Hotel $hotel, array $definition, int $index): bool
    {
        if ($hotel->getMedia('images')->isNotEmpty()) {
            return true;
        }

        if (! DemoImageGenerator::available()) {
            return false;
        }

        foreach ($definition['images'] as $position => $label) {
            $bytes = DemoImageGenerator::hotel(
                $definition['name_en'],
                $definition['city'] . ' - ' . $definition['star_rating'] . ' Stars - ' . $label,
                ($index + 1) * 100 + $position
            );

            if ($bytes === null) {
                return false;
            }

            $hotel->addMediaFromString($bytes)
                ->usingFileName($definition['key'] . '-' . ($position + 1) . '.jpg')
                ->usingName($definition['name_en'] . ' - ' . $label)
                ->toMediaCollection('images');
        }

        return true;
    }

    /** الفنادق التجريبية مفهرسة بالـ key. */
    public static function hotels(): \Illuminate\Support\Collection
    {
        $names = array_column(self::HOTELS, 'name_en', 'key');

        $hotels = Hotel::whereIn('name_en', $names)->get()->keyBy('name_en');

        return collect($names)->map(fn ($name) => $hotels->get($name))->filter();
    }
}
