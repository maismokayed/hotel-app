<?php

namespace Database\Seeders;

use App\Models\City;
use App\Models\Hotel;
use App\Models\Service;
use Illuminate\Database\Seeder;

/**
 * فنادق البيانات التجريبية + الخدمات المرتبطة بها.
 *
 * الفنادق مأخوذة من بيانات حقيقية (29 فندقاً موزعة على 10 محافظات)، وليست
 * مولّدة عشوائياً. لا يوجد توليد صور هون بشكل مقصود - الصور تُضاف لاحقاً
 * يدوياً (مثلاً عبر Postman) بعد إدخال هذه البيانات.
 *
 * ملاحظة: فنادق "ريف دمشق" استُبعدت بالكامل من مصدر البيانات الأصلي.
 */
class DemoHotelsSeeder extends Seeder
{
    /** الخدمات المتاحة في النظام (تُنشأ إن لم تكن موجودة). */
    public const SERVICES = [
        'wifi' => ['name_ar' => 'انترنت', 'name_en' => 'WiFi'],
        'restaurant' => ['name_ar' => 'مطعم', 'name_en' => 'Restaurant'],
        'cafe' => ['name_ar' => 'كافيه', 'name_en' => 'Cafe'],
        'buffet' => ['name_ar' => 'بوفيه', 'name_en' => 'Buffet'],
        'transport' => ['name_ar' => 'توصيل اىل الفندق', 'name_en' => 'Transportation Service'],
        'service_24h' => ['name_ar' => 'خدمة 24 ساعة', 'name_en' => '24-Hour Service'],
        'parking' => ['name_ar' => 'موقف سيارات', 'name_en' => 'Parking'],
        'room_serv' => ['name_ar' => 'توصيل طلبات', 'name_en' => 'Room Service'],
        'meeting_hall' => ['name_ar' => 'قاعة اجتماعات', 'name_en' => 'Meeting Hall'],
        'wedding_hall' => ['name_ar' => 'قاعة أفراح', 'name_en' => 'Wedding Hall'],
        'gym' => ['name_ar' => 'نادي رياضي', 'name_en' => 'Gym'],
        'pool' => ['name_ar' => 'مسبح', 'name_en' => 'Swimming Pool'],
        'childcare' => ['name_ar' => 'حضانة أطفال', 'name_en' => 'Childcare'],
    ];

    /**
     * الفنادق التجريبية (29 فندقاً).
     * manager: الـ key من DemoUsersSeeder::MANAGERS
     * city_ar/city_en: تُستخدم لإيجاد/إنشاء المدينة عبر City::firstOrCreate (name_ar فريد بقاعدة البيانات)
     */
    public const HOTELS = [
        // حلب
        [
            'key'            => 'al_shahba_hotel',
            'manager'        => 'manager_halab',
            'city_ar'        => 'حلب',
            'city_en'        => 'Aleppo',
            'name_ar'        => 'فندق الشهباء',
            'name_en'        => 'Al-Shahba Hotel',
            'description_ar' => 'يعد أحد أعرق وأفخم فنادق مدينة حلب حيث يقع في موقع حيوي بالقرب من جامعة حلب والملعب البلدي ويقدم خدمات فندقية راقية مع إطلالات مميزة على معالم المدينة العريقة.',
            'description_en' => 'One of the oldest and most luxurious hotels in Aleppo, set in a lively area near Aleppo University and the municipal stadium. It offers upscale hotel services with distinctive views over the city\'s historic landmarks.',
            'address_ar'     => 'حلب - المرييديان - شارع لؤي كيال',
            'address_en'     => 'Aleppo - Al-Meridian - Loay Kayali Street',
            'phone'          => '+963 930 300 838',
            'email'          => 'al_shahba_hotel@email.com',
            'facebook_url'   => 'https://www.facebook.com/shahbaaleppohotel/',
            'instagram_url'  => 'https://www.instagram.com/shahba_aleppo_hotel/',
            'star_rating'    => 5,
            'is_active'      => true,
            'services'       => ['gym', 'room_serv', 'wifi', 'childcare', 'transport', 'buffet', 'restaurant', 'cafe', 'parking', 'meeting_hall', 'wedding_hall', 'pool', 'service_24h'],
        ],
        [
            'key'            => 'riga_palace_hotel',
            'manager'        => 'manager_halab',
            'city_ar'        => 'حلب',
            'city_en'        => 'Aleppo',
            'name_ar'        => 'فندق ريغا بالاس',
            'name_en'        => 'Riga Palace Hotel',
            'description_ar' => 'يقع الفندق في قلب مدينة حلب النابضة بالحياة، ويتميز بموقعه الاستراتيجي القريب من المعالم التاريخية، حيث يبعد عن قلعة حلب 7 دقائق سيرًا على الأقدام.',
            'description_en' => 'Located in the vibrant heart of Aleppo, this hotel enjoys a strategic position close to historic landmarks, just a 7-minute walk from the Citadel of Aleppo.',
            'address_ar'     => 'حلب - شارع زكي الأرسوزي',
            'address_en'     => 'Aleppo - Zaki Al-Arsouzi Street',
            'phone'          => '+963 956 741 618',
            'email'          => 'rigapalacehotel@gmail.com',
            'facebook_url'   => 'https://www.facebook.com/p/Riga-Palace-Hotel-100091843291132/',
            'instagram_url'  => 'https://www.instagram.com/rigapalacehotel/',
            'star_rating'    => 4,
            'is_active'      => true,
            'services'       => ['gym', 'room_serv', 'wifi', 'transport', 'buffet', 'restaurant', 'cafe', 'parking', 'meeting_hall', 'wedding_hall', 'service_24h'],
        ],
        [
            'key'            => 'sheraton_aleppo_hotel',
            'manager'        => 'manager_halab2',
            'city_ar'        => 'حلب',
            'city_en'        => 'Aleppo',
            'name_ar'        => 'فندق الشيراتون',
            'name_en'        => 'Sheraton Aleppo Hotel',
            'description_ar' => 'فندق خمس نجوم يقع في قلب مدينة حلب القديمة، وتعكس هندسته المعمارية النمط التقليدي القديم للمباني الحلبية، إذ يجمع بين واجهة تاريخية تعود للقرن الخامس عشر ومساحات داخلية عصرية.',
            'description_en' => 'A five-star hotel in the heart of Old Aleppo. Its architecture reflects the traditional style of Aleppo\'s old buildings, combining a 15th-century historic facade with modern interior spaces.',
            'address_ar'     => 'حلب - باب الفرج',
            'address_en'     => 'Aleppo - Bab Al-Faraj',
            'phone'          => '+963 992 121 111',
            'email'          => 'Reservation@sheraton-aleppo.com',
            'facebook_url'   => 'https://www.facebook.com/p/Sheraton-Aleppo-Hotel-100064926990907/',
            'instagram_url'  => 'https://www.instagram.com/sheraton_aleppo_hotel/',
            'star_rating'    => 5,
            'is_active'      => true,
            'services'       => ['gym', 'wifi', 'transport', 'buffet', 'restaurant', 'cafe', 'parking', 'meeting_hall', 'wedding_hall', 'pool', 'service_24h'],
        ],
        [
            'key'            => 'ezes_hotel',
            'manager'        => 'manager_halab3',
            'city_ar'        => 'حلب',
            'city_en'        => 'Aleppo',
            'name_ar'        => 'فندق إيزيس',
            'name_en'        => 'Ezes Hotel',
            'description_ar' => 'فندق سياحي مصنف ضمن فئة الأربع نجوم ويتميز بموقعه الحيوي في وسط مدينة حلب، ويعد خيارًا ممتازًا للمسافرين والسياح ورجال الأعمال بفضل قربه من الأسواق التجارية والمعالم الأثرية.',
            'description_en' => 'A four-star tourist hotel with a lively location in central Aleppo, an excellent choice for travelers, tourists and business people thanks to its proximity to commercial markets and archaeological sites.',
            'address_ar'     => 'حلب - العزيزية - جانب نادي الجلاء الرياضي',
            'address_en'     => 'Aleppo - Al-Aziziyah - next to Al-Jalaa Sports Club',
            'phone'          => '+963 945 200 057',
            'email'          => 'ezes_hotel@email.com',
            'facebook_url'   => 'https://www.facebook.com/p/EZES-HOTEL-Restaurant-100063707756859/',
            'instagram_url'  => 'https://www.instagram.com/ezeshotel/',
            'star_rating'    => 4,
            'is_active'      => true,
            'services'       => ['room_serv', 'wifi', 'transport', 'buffet', 'restaurant', 'cafe', 'parking', 'meeting_hall', 'wedding_hall', 'service_24h'],
        ],
        [
            'key'            => 'pullman_alshahba_hotel',
            'manager'        => 'manager_halab4',
            'city_ar'        => 'حلب',
            'city_en'        => 'Aleppo',
            'name_ar'        => 'فندق البولمان الشهباء',
            'name_en'        => 'Pullman Alshahba Hotel',
            'description_ar' => 'يقع الفندق في حي الموكامبو الراقي، ويتألف من 6 طوابق تضم أكثر من 100 غرفة وجناح، ويقدم تجربة إقامة تجمع بين الأصالة الحلبية والرفاهية العصرية.',
            'description_en' => 'Located in the upscale Mukambo district, this 6-floor hotel houses more than 100 rooms and suites, offering a stay that blends Aleppan authenticity with modern luxury.',
            'address_ar'     => 'حلب - الموكامبو - مقابل مشفى الجامعة',
            'address_en'     => 'Aleppo - Mukambo - opposite the University Hospital',
            'phone'          => '+963 949 055 527',
            'email'          => 'pullman_alshahba_hotel@email.com',
            'facebook_url'   => 'https://www.facebook.com/p/%D9%81%D9%86%D8%AF%D9%82-%D8%A8%D9%88%D9%84%D9%85%D8%A7%D9%86-%D8%A7%D9%84%D8%B4%D9%87%D8%A8%D8%A7%D8%A1-Pullman-Alshahba-Hotel-100063954465385/',
            'instagram_url'  => 'https://www.instagram.com/pullman.alshahba.hotel/',
            'star_rating'    => 4,
            'is_active'      => true,
            'services'       => ['wifi', 'childcare', 'transport', 'buffet', 'restaurant', 'cafe', 'parking', 'meeting_hall', 'wedding_hall', 'service_24h'],
        ],

        // دمشق
        [
            'key'            => 'ebla_hotel',
            'manager'        => 'manager_sham1',
            'city_ar'        => 'دمشق',
            'city_en'        => 'Damascus',
            'name_ar'        => 'فندق إيبلا',
            'name_en'        => 'Ebla Hotel',
            'description_ar' => 'قصر سياحي فاخر من فئة الخمس نجوم يتميز بموقعه وسط واحة خضراء وبحيرات اصطناعية، ويبعد نحو 15 دقيقة عن كل من وسط مدينة دمشق ومطارها.',
            'description_en' => 'A luxurious five-star tourist palace set amid a green oasis and artificial lakes, about 15 minutes from both downtown Damascus and its airport.',
            'address_ar'     => 'دمشق - أوتوستراد المطار - منطقة شبعا',
            'address_en'     => 'Damascus - Airport Highway - Sheb\'a area',
            'phone'          => '+963 998 000 060',
            'email'          => 'ebla_hotel@email.com',
            'facebook_url'   => 'https://www.facebook.com/EblaHotel/',
            'instagram_url'  => 'https://www.instagram.com/ebla.hotel/',
            'star_rating'    => 5,
            'is_active'      => true,
            'services'       => ['gym', 'room_serv', 'wifi', 'childcare', 'transport', 'buffet', 'restaurant', 'cafe', 'parking', 'meeting_hall', 'wedding_hall', 'pool', 'service_24h'],
        ],
        [
            'key'            => 'cham_palace_hotel',
            'manager'        => 'manager_sham2',
            'city_ar'        => 'دمشق',
            'city_en'        => 'Damascus',
            'name_ar'        => 'فندق الشام',
            'name_en'        => 'Cham Palace Hotel',
            'description_ar' => 'يقع فندق الشام وسط مدينة دمشق، ويتميز بموقعه القريب من الوجهات الاقتصادية والأسواق، وبتصميمه الراقي الملائم للبيئة الشامية.',
            'description_en' => 'Located in central Damascus, close to economic hubs and markets, with an elegant design suited to the Damascene setting.',
            'address_ar'     => 'دمشق - شارع ميسلون',
            'address_en'     => 'Damascus - Maysaloun Street',
            'phone'          => '+963 992 222 679',
            'email'          => 'cham_palace_hotel@email.com',
            'facebook_url'   => 'https://www.facebook.com/211883675494373',
            'instagram_url'  => 'https://www.instagram.com/champalacehotel/',
            'star_rating'    => 5,
            'is_active'      => true,
            'services'       => ['gym', 'room_serv', 'wifi', 'transport', 'buffet', 'restaurant', 'cafe', 'parking', 'meeting_hall', 'wedding_hall', 'service_24h'],
        ],
        [
            'key'            => 'royal_semiramis_hotel',
            'manager'        => 'manager_sham3',
            'city_ar'        => 'دمشق',
            'city_en'        => 'Damascus',
            'name_ar'        => 'فندق رويال سميراميس',
            'name_en'        => 'Royal Semiramis Hotel',
            'description_ar' => 'فندق خمس نجوم يقع في قلب دمشق، ويجمع بين الأناقة الكلاسيكية والتصميم الحديث، ليمثل رمزًا للفخامة والضيافة في المدينة.',
            'description_en' => 'A five-star hotel in the heart of Damascus, blending classic elegance with modern design, standing as a symbol of luxury and hospitality in the city.',
            'address_ar'     => 'دمشق - منطقة الأعمال التجارية - قرب جسر فيكتوريا',
            'address_en'     => 'Damascus - Commercial Business District - near Victoria Bridge',
            'phone'          => '+963 946 900 522',
            'email'          => 'royal_semiramis_hotel@email.com',
            'facebook_url'   => 'https://www.facebook.com/royal.semiramis',
            'instagram_url'  => '',
            'star_rating'    => 5,
            'is_active'      => true,
            'services'       => ['gym', 'room_serv', 'wifi', 'childcare', 'transport', 'buffet', 'restaurant', 'cafe', 'parking', 'meeting_hall', 'wedding_hall', 'pool', 'service_24h'],
        ],
        [
            'key'            => 'four_seasons_damascus',
            'manager'        => 'manager_sham4',
            'city_ar'        => 'دمشق',
            'city_en'        => 'Damascus',
            'name_ar'        => 'فندق فورسيزون',
            'name_en'        => 'Four Seasons Damascus',
            'description_ar' => 'صرح فخم من فئة الخمس نجوم يتكون من 23 طابقًا ويضم 297 غرفة وجناحًا، بُني بتصميم يدمج بين الطراز العثماني والفارسي والمحلي ليغدو أحد أبرز معالم العاصمة السورية.',
            'description_en' => 'A grand five-star landmark of 23 floors with 297 rooms and suites, built in a design blending Ottoman, Persian and local styles, making it one of the most notable landmarks of the Syrian capital.',
            'address_ar'     => 'دمشق - شارع شكري القوتلي',
            'address_en'     => 'Damascus - Shukri Al-Quwatli Street',
            'phone'          => '+963 113 391 000',
            'email'          => 'four_seasons_damascus@email.com',
            'facebook_url'   => 'https://www.facebook.com/fourseasonsdama/',
            'instagram_url'  => 'https://www.instagram.com/four_seasons_hotel_damascus/',
            'star_rating'    => 5,
            'is_active'      => true,
            'services'       => ['gym', 'room_serv', 'wifi', 'childcare', 'transport', 'buffet', 'restaurant', 'cafe', 'parking', 'meeting_hall', 'wedding_hall', 'pool', 'service_24h'],
        ],
        [
            'key'            => 'al_nahda_hotel',
            'manager'        => 'manager_sham5',
            'city_ar'        => 'دمشق',
            'city_en'        => 'Damascus',
            'name_ar'        => 'فندق النهضة',
            'name_en'        => 'Al-Nahda Hotel',
            'description_ar' => 'يتوسط الفندق مدينة دمشق، ويتميز بموقع مثالي لقربه من الأسواق التاريخية والمناطق التجارية.',
            'description_en' => 'Centrally located in Damascus, with an ideal position close to historic markets and commercial areas.',
            'address_ar'     => 'دمشق - الحريقة - مقابل جامع الدرويشية',
            'address_en'     => 'Damascus - Al-Hariqa - opposite Al-Darwishiyya Mosque',
            'phone'          => '+963 980 893 901',
            'email'          => 'al_nahda_hotel@email.com',
            'facebook_url'   => '',
            'instagram_url'  => '',
            'star_rating'    => 2,
            'is_active'      => true,
            'services'       => ['room_serv', 'wifi', 'buffet', 'restaurant', 'cafe', 'service_24h'],
        ],
        [
            'key'            => 'dama_rose_hotel',
            'manager'        => 'manager_sham6',
            'city_ar'        => 'دمشق',
            'city_en'        => 'Damascus',
            'name_ar'        => 'فندق داما روز',
            'name_en'        => 'Dama Rose Hotel',
            'description_ar' => 'أحد أبرز فنادق الخمس نجوم الفاخرة في العاصمة السورية، يتميز بإطلالات ساحرة على المدينة وقربه من المعالم الثقافية والتاريخية، ويعد نقطة إقامة رئيسية مفضلة للوفود الدبلوماسية ورجال الأعمال.',
            'description_en' => 'One of the most prominent luxury five-star hotels in the Syrian capital, with charming views over the city and proximity to cultural and historic landmarks, a preferred stay for diplomatic delegations and business travelers.',
            'address_ar'     => 'دمشق - منطقة أبو رمانة - شارع شكري القوتلي',
            'address_en'     => 'Damascus - Abu Rummaneh - Shukri Al-Quwatli Street',
            'phone'          => '+963 945 000 102',
            'email'          => 'info@damarose.com',
            'facebook_url'   => 'https://www.facebook.com/DamaRoseHotel/',
            'instagram_url'  => 'https://www.instagram.com/damarose_hotel/',
            'star_rating'    => 5,
            'is_active'      => true,
            'services'       => ['gym', 'room_serv', 'wifi', 'childcare', 'transport', 'buffet', 'restaurant', 'cafe', 'parking', 'meeting_hall', 'wedding_hall', 'pool', 'service_24h'],
        ],

        // حمص
        [
            'key'            => 'louis_inn_hotel',
            'manager'        => 'manager_wasat',
            'city_ar'        => 'حمص',
            'city_en'        => 'Homs',
            'name_ar'        => 'فندق لويس إن',
            'name_en'        => 'Louis Inn Hotel',
            'description_ar' => 'يقع في قلب مدينة حمص القديمة، ويتميز بطابع تاريخي وتصميم مستوحى من الحقبة التاريخية للمدينة، مع خدمات ومرافق عصرية مريحة تناسب السياح.',
            'description_en' => 'Located in the heart of Old Homs, with a historic character and design inspired by the city\'s heritage, alongside comfortable modern services and facilities for tourists.',
            'address_ar'     => 'حمص - بستان الديوان - جانب كنيسة الأربعين',
            'address_en'     => 'Homs - Bustan Al-Diwan - next to the Church of the Forty Martyrs',
            'phone'          => '+963 989 401 164',
            'email'          => 'louis_inn_hotel@email.com',
            'facebook_url'   => 'https://www.facebook.com/p/Louis-Inn-Hotel-and-Restaurant-100086929524074/',
            'instagram_url'  => 'https://www.instagram.com/louis.inn/',
            'star_rating'    => 3,
            'is_active'      => true,
            'services'       => ['room_serv', 'wifi', 'childcare', 'transport', 'buffet', 'restaurant', 'cafe', 'parking', 'meeting_hall', 'wedding_hall', 'service_24h'],
        ],
        [
            'key'            => 'safir_hotel_homs',
            'manager'        => 'manager_homs2',
            'city_ar'        => 'حمص',
            'city_en'        => 'Homs',
            'name_ar'        => 'فندق سفير',
            'name_en'        => 'Safir Hotel Homs',
            'description_ar' => 'واحد من أبرز الفنادق العريقة في مدينة حمص، يتميز بقربه من مركز المدينة والأسواق، ويضم 92 غرفة وجناحًا ومسبحًا خارجيًا، ليقدم مزيجًا من الرفاهية العصرية وحفاوة الضيافة العربية الأصيلة.',
            'description_en' => 'One of the most prominent long-established hotels in Homs, close to the city center and markets, with 92 rooms and suites and an outdoor pool, blending modern luxury with authentic Arab hospitality.',
            'address_ar'     => 'حمص - حي الإنشاءات - جانب رجب الجمال',
            'address_en'     => 'Homs - Al-Insha\'at district - next to Rajab Al-Jamal',
            'phone'          => '+963 988 410 007',
            'email'          => 'reservations.homs@safirhotels.com',
            'facebook_url'   => 'https://www.facebook.com/SafirHotelHoms/',
            'instagram_url'  => 'https://www.instagram.com/safir_hotel_homs/',
            'star_rating'    => 5,
            'is_active'      => true,
            'services'       => ['gym', 'room_serv', 'wifi', 'childcare', 'transport', 'buffet', 'restaurant', 'cafe', 'parking', 'meeting_hall', 'wedding_hall', 'pool', 'service_24h'],
        ],

        // حماة
        [
            'key'            => 'bait_al_sharq_hotel',
            'manager'        => 'manager_hama1',
            'city_ar'        => 'حماة',
            'city_en'        => 'Hama',
            'name_ar'        => 'فندق بيت الشرق',
            'name_en'        => 'Bait Al-Sharq Hotel',
            'description_ar' => 'يقع بشكل مثالي في قلب حماة، ويوفر لضيوفه وصولًا سهلًا إلى المدينة ذات التراث الثقافي الغني والأسواق النابضة بالحياة والمطاعم الشهيرة.',
            'description_en' => 'Ideally located in the heart of Hama, offering guests easy access to the city\'s rich cultural heritage, lively markets and famous restaurants.',
            'address_ar'     => 'حماة - ساحة العاصي - شارع الجلاء',
            'address_en'     => 'Hama - Al-Assi Square - Al-Jalaa Street',
            'phone'          => '+963 996 874 444',
            'email'          => 'Orienthousehotel@hotmail.com',
            'facebook_url'   => 'https://www.facebook.com/baitalsharqhotel/',
            'instagram_url'  => 'https://www.instagram.com/bait.al_sharq/',
            'star_rating'    => 4,
            'is_active'      => true,
            'services'       => ['room_serv', 'wifi', 'transport', 'buffet', 'restaurant', 'cafe', 'service_24h'],
        ],
        [
            'key'            => 'afamia_cham_palace',
            'manager'        => 'manager_hama2',
            'city_ar'        => 'حماة',
            'city_en'        => 'Hama',
            'name_ar'        => 'فندق أفاميا شام بالاس',
            'name_en'        => 'Afamia Cham Palace',
            'description_ar' => 'يقع بشكل مثالي في قلب حماة، قرب ساحة العاصي ونواعيرها التاريخية، ويعود تأسيسه إلى عام 1991، ويتألف من 8 طوابق ليقدم مزيجًا بين أصالة المدينة وعراقتها.',
            'description_en' => 'Ideally located in the heart of Hama, near Al-Assi Square and its historic norias (waterwheels). Established in 1991, this 8-floor hotel blends the city\'s authenticity and heritage.',
            'address_ar'     => 'حماة - منطقة الزنبقي - شارع أبي نواس',
            'address_en'     => 'Hama - Al-Zanbaqi area - Abu Nawas Street',
            'phone'          => '+963 993 399 433',
            'email'          => 'afamia_cham_palace@email.com',
            'facebook_url'   => 'https://www.facebook.com/p/Afamia-AlSham-100064402313787/',
            'instagram_url'  => 'https://www.instagram.com/afamia.alsham_/',
            'star_rating'    => 5,
            'is_active'      => true,
            'services'       => ['room_serv', 'wifi', 'transport', 'buffet', 'restaurant', 'cafe', 'parking', 'meeting_hall', 'wedding_hall', 'pool', 'service_24h'],
        ],

        // اللاذقية
        [
            'key'            => 'park_plaza_hotel',
            'manager'        => 'manager_latakia',
            'city_ar'        => 'اللاذقية',
            'city_en'        => 'Latakia',
            'name_ar'        => 'فندق بارك بلازا',
            'name_en'        => 'Park Plaza Hotel',
            'description_ar' => 'معلم سياحي عريق يعود تاريخ بنائه إلى عام 1929 في مصيف صلنفة السوري على ارتفاع 1200 متر، ويمزج تصميمه بين الأصالة التاريخية ورقي الخدمات الفندقية الحديثة.',
            'description_en' => 'A historic landmark built in 1929 in the Syrian mountain resort of Slunfeh, 1,200 meters above sea level, blending historic character with modern hotel services.',
            'address_ar'     => 'اللاذقية - صلنفة',
            'address_en'     => 'Latakia - Slunfeh',
            'phone'          => '+963 989 700 207',
            'email'          => 'park_plaza_hotel@email.com',
            'facebook_url'   => '',
            'instagram_url'  => '',
            'star_rating'    => 3,
            'is_active'      => true,
            'services'       => ['gym', 'room_serv', 'wifi', 'transport', 'buffet', 'restaurant', 'cafe', 'parking', 'meeting_hall', 'wedding_hall', 'pool', 'service_24h'],
        ],
        [
            'key'            => 'vetro_hotel',
            'manager'        => 'manager_latakia',
            'city_ar'        => 'اللاذقية',
            'city_en'        => 'Latakia',
            'name_ar'        => 'فندق فيرتو',
            'name_en'        => 'Vetro Hotel',
            'description_ar' => 'يقع بشكل مثالي في قلب اللاذقية، إحدى أكثر المدن السورية سحرًا، ويوفر لضيوفه وصولًا سهلًا إلى المدينة وأسواقها النابضة بالحياة.',
            'description_en' => 'Ideally located in the heart of Latakia, one of Syria\'s most charming cities, offering guests easy access to the city and its lively markets.',
            'address_ar'     => 'اللاذقية - حي الزراعة - مقابل كنيسة مار ريشة',
            'address_en'     => 'Latakia - Al-Zira\'a district - opposite Mar Risha Church',
            'phone'          => '+963 936 270 000',
            'email'          => 'vetro_hotel@email.com',
            'facebook_url'   => 'https://www.facebook.com/vetrohotel/',
            'instagram_url'  => 'https://www.instagram.com/vetrohotel_/',
            'star_rating'    => 4,
            'is_active'      => true,
            'services'       => ['room_serv', 'wifi', 'childcare', 'transport', 'buffet', 'restaurant', 'cafe', 'parking', 'meeting_hall', 'service_24h'],
        ],
        [
            'key'            => 'lamira_resort',
            'manager'        => 'manager_latakia2',
            'city_ar'        => 'اللاذقية',
            'city_en'        => 'Latakia',
            'name_ar'        => 'فندق لاميرا',
            'name_en'        => 'Lamira Resort',
            'description_ar' => 'منشأة سياحية من فئة الخمس نجوم تقع على الشاطئ الأزرق في مدينة اللاذقية، وتتميز بإطلالات مباشرة على ساحل البحر المتوسط وبمساحات واسعة.',
            'description_en' => 'A five-star tourist facility on the Blue Beach in Latakia, with direct views over the Mediterranean coast and expansive grounds.',
            'address_ar'     => 'اللاذقية - الشاطئ الأزرق',
            'address_en'     => 'Latakia - Blue Beach',
            'phone'          => '+963 995 400 500',
            'email'          => 'lamira_resort@email.com',
            'facebook_url'   => 'https://www.facebook.com/lamirahotel/',
            'instagram_url'  => 'https://www.instagram.com/lamiraresort/',
            'star_rating'    => 5,
            'is_active'      => true,
            'services'       => ['gym', 'room_serv', 'wifi', 'childcare', 'transport', 'buffet', 'restaurant', 'cafe', 'parking', 'meeting_hall', 'wedding_hall', 'pool', 'service_24h'],
        ],
        [
            'key'            => 'razzouk_hotel',
            'manager'        => 'manager_latakia3',
            'city_ar'        => 'اللاذقية',
            'city_en'        => 'Latakia',
            'name_ar'        => 'فندق رزوق',
            'name_en'        => 'Razzouk Hotel',
            'description_ar' => 'يقع فندق رزوق في بلدة كسب، ويعد ملاذًا هادئًا للاسترخاء وسط الغابات الكثيفة والطبيعة الجبلية الخلابة، ويشتهر بإطلالاته الساحرة بين الوديان.',
            'description_en' => 'Razzouk Hotel is located in the town of Kessab, a peaceful retreat amid dense forests and stunning mountain scenery, known for its charming valley views.',
            'address_ar'     => 'اللاذقية - بلدة كسب',
            'address_en'     => 'Latakia - Kessab town',
            'phone'          => '+963 933 354 992',
            'email'          => 'razzouk_hotel@email.com',
            'facebook_url'   => 'https://www.facebook.com/p/Razzouk-Hotel-%D9%81%D9%86%D8%AF%D9%82-%D8%B1%D8%B2%D9%88%D9%82-%D8%A7%D9%84%D8%B3%D9%8A%D8%A7%D8%AD%D9%8A-100063782602738/',
            'instagram_url'  => 'https://www.instagram.com/razzouk_hotel/',
            'star_rating'    => 3,
            'is_active'      => true,
            'services'       => ['room_serv', 'wifi', 'transport', 'buffet', 'restaurant', 'cafe', 'meeting_hall', 'wedding_hall', 'service_24h'],
        ],

        // طرطوس
        [
            'key'            => 'shahin_tower_hotel_resort',
            'manager'        => 'manager_tartus',
            'city_ar'        => 'طرطوس',
            'city_en'        => 'Tartus',
            'name_ar'        => 'فندق ومنتجع برج شاهين',
            'name_en'        => 'Shahin Tower Hotel & Resort',
            'description_ar' => 'برج فندقي في قلب مدينة طرطوس الساحلية، يتألف من 21 طابقًا ليكون من أعلى الأبراج في المنطقة، ويتميز بإطلالات بانورامية ساحرة على البحر الأبيض المتوسط.',
            'description_en' => 'A 21-floor hotel tower in the heart of coastal Tartous, one of the tallest towers in the region, offering stunning panoramic views over the Mediterranean Sea.',
            'address_ar'     => 'طرطوس - حي الكرامة - شارع طارق بن زياد',
            'address_en'     => 'Tartous - Al-Karama district - Tariq ibn Ziyad Street',
            'phone'          => '+963 932 030 030',
            'email'          => 'shahin_tower_hotel_resort@email.com',
            'facebook_url'   => 'https://www.facebook.com/ShahinTowerHotel/',
            'instagram_url'  => '',
            'star_rating'    => 4,
            'is_active'      => true,
            'services'       => ['gym', 'room_serv', 'wifi', 'childcare', 'transport', 'buffet', 'restaurant', 'cafe', 'parking', 'meeting_hall', 'wedding_hall', 'pool', 'service_24h'],
        ],
        [
            'key'            => 'royal_inn',
            'manager'        => 'manager_tartus',
            'city_ar'        => 'طرطوس',
            'city_en'        => 'Tartus',
            'name_ar'        => 'فندق رويال إن',
            'name_en'        => 'Royal Inn',
            'description_ar' => 'فندق فاخر من فئة الأربع نجوم يقع على الكورنيش البحري في مدينة طرطوس، ويتميز بإطلالات مباشرة على البحر الأبيض المتوسط وجزيرة أرواد.',
            'description_en' => 'A luxury four-star hotel on the Tartous seafront corniche, with direct views over the Mediterranean Sea and Arwad Island.',
            'address_ar'     => 'طرطوس - الكورنيش البحري',
            'address_en'     => 'Tartous - Seafront Corniche',
            'phone'          => '+963 998 730 730',
            'email'          => 'royal_inn@email.com',
            'facebook_url'   => 'https://www.facebook.com/royalinn2020/',
            'instagram_url'  => '',
            'star_rating'    => 4,
            'is_active'      => true,
            'services'       => ['gym', 'room_serv', 'wifi', 'transport', 'buffet', 'restaurant', 'cafe', 'parking', 'meeting_hall', 'wedding_hall', 'service_24h'],
        ],
        [
            'key'            => 'al_sufara_hotel',
            'manager'        => 'manager_tartus2',
            'city_ar'        => 'طرطوس',
            'city_en'        => 'Tartus',
            'name_ar'        => 'فندق السفراء',
            'name_en'        => 'Al-Sufara Hotel',
            'description_ar' => 'يتميز بموقعه الاستراتيجي على الكورنيش البحري مقابل جزيرة ومرفأ أرواد، ويمنح الزوار تجربة تجمع بين سحر الطبيعة الساحلية وقرب المكان من الأسواق.',
            'description_en' => 'A strategic location on the seafront corniche facing Arwad Island and its harbor, offering visitors an experience combining coastal charm with proximity to markets.',
            'address_ar'     => 'طرطوس - الكورنيش البحري',
            'address_en'     => 'Tartous - Seafront Corniche',
            'phone'          => '+963 996 602 655',
            'email'          => 'al_sufara_hotel@email.com',
            'facebook_url'   => 'https://www.facebook.com/alsfraahotel/',
            'instagram_url'  => '',
            'star_rating'    => 3,
            'is_active'      => true,
            'services'       => ['room_serv', 'wifi', 'childcare', 'transport', 'buffet', 'restaurant', 'cafe', 'parking', 'service_24h'],
        ],
        [
            'key'            => 'mashta_al_helou_resort',
            'manager'        => 'manager_tartus3',
            'city_ar'        => 'طرطوس',
            'city_en'        => 'Tartus',
            'name_ar'        => 'فندق ومنتجع مشتى الحلو',
            'name_en'        => 'Mashta Al-Helou Resort',
            'description_ar' => 'فندق ومنتجع بعمارة فريدة يضم كل ما في المنتجعات العالمية من ميزات وخدمات، ويحتوي على شاليهات وغرف ومسبح وملاعب.',
            'description_en' => 'A resort with unique architecture offering all the features and services of international resorts, including chalets, rooms, a pool and sports courts.',
            'address_ar'     => 'طرطوس - مشتى الحلو - قرب دوار الكفرون',
            'address_en'     => 'Tartous - Mashta Al-Helou - near Al-Kafroun roundabout',
            'phone'          => '+963 933 145 782',
            'email'          => 'mashta_al_helou_resort@email.com',
            'facebook_url'   => '',
            'instagram_url'  => '',
            'star_rating'    => 5,
            'is_active'      => true,
            'services'       => ['gym', 'room_serv', 'wifi', 'childcare', 'transport', 'buffet', 'restaurant', 'cafe', 'parking', 'meeting_hall', 'wedding_hall', 'pool', 'service_24h'],
        ],

        // دير الزور
        [
            'key'            => 'nour_al_sham_hotel',
            'manager'        => 'manager_deir1',
            'city_ar'        => 'دير الزور',
            'city_en'        => 'Deir ez-Zor',
            'name_ar'        => 'فندق نور الشام',
            'name_en'        => 'Nour Al-Sham Hotel',
            'description_ar' => 'وجهة مثالية ومريحة للزوار، تمنح ضيوفها تجربة إقامة هادئة ومميزة بفضل مرافقها التي تجمع بين الأناقة والخدمة الودية.',
            'description_en' => 'An ideal, comfortable destination for visitors, offering guests a calm and distinctive stay thanks to facilities that combine elegance with friendly service.',
            'address_ar'     => 'دير الزور - حي الجورة - شارع الوادي',
            'address_en'     => 'Deir ez-Zor - Al-Joura district - Al-Wadi Street',
            'phone'          => '+963 984 761 069',
            'email'          => 'nour_al_sham_hotel@email.com',
            'facebook_url'   => '',
            'instagram_url'  => '',
            'star_rating'    => 3,
            'is_active'      => true,
            'services'       => ['room_serv', 'wifi', 'transport', 'buffet', 'restaurant', 'cafe', 'parking', 'service_24h'],
        ],

        // الرقة
        [
            'key'            => 'al_rasheed_hotel',
            'manager'        => 'manager_raqqa1',
            'city_ar'        => 'الرقة',
            'city_en'        => 'Raqqa',
            'name_ar'        => 'فندق الرشيد',
            'name_en'        => 'Al-Rasheed Hotel',
            'description_ar' => 'معلم جديد وخدمي في مدينة الرقة، يمنح ضيوفه إقامة مميزة تجمع بين الراحة والفخامة.',
            'description_en' => 'A new service landmark in Raqqa, offering guests a distinctive stay that combines comfort and luxury.',
            'address_ar'     => 'الرقة - شمال دوار الدلة',
            'address_en'     => 'Raqqa - north of Al-Dallah roundabout',
            'phone'          => '+963 932 206 974',
            'email'          => 'al_rasheed_hotel@email.com',
            'facebook_url'   => '',
            'instagram_url'  => '',
            'star_rating'    => 3,
            'is_active'      => true,
            'services'       => ['room_serv', 'wifi', 'restaurant', 'cafe', 'service_24h'],
        ],
        [
            'key'            => 'hotel_café_47',
            'manager'        => 'manager_raqqa2',
            'city_ar'        => 'الرقة',
            'city_en'        => 'Raqqa',
            'name_ar'        => 'فندق فورتي سفن',
            'name_en'        => 'Hotel & Café 47',
            'description_ar' => 'مكان يجمع بين الأجواء الراقية والضيافة المميزة والمذاق الذي يستحق أن يجرب، يمنح ضيوفه إقامة مميزة تجمع بين الراحة والفخامة.',
            'description_en' => 'A place combining an upscale atmosphere, distinctive hospitality and flavors worth trying, offering guests a distinctive stay that combines comfort and luxury.',
            'address_ar'     => 'الرقة - منطقة دوار النعيم - قرب دوار السبع بحرات',
            'address_en'     => 'Raqqa - Al-Naeem roundabout area - near the Seven Fountains roundabout',
            'phone'          => '+963 985 290 296',
            'email'          => 'hotel_caf_47@email.com',
            'facebook_url'   => 'https://www.facebook.com/61587483724639',
            'instagram_url'  => 'https://www.instagram.com/forty._seven7/',
            'star_rating'    => 4,
            'is_active'      => true,
            'services'       => ['room_serv', 'wifi', 'transport', 'buffet', 'restaurant', 'cafe', 'service_24h'],
        ],

        // إدلب
        [
            'key'            => 'al_nil_international_resort',
            'manager'        => 'manager_idlib1',
            'city_ar'        => 'إدلب',
            'city_en'        => 'Idlib',
            'name_ar'        => 'فندق منتجع النيل الدولي',
            'name_en'        => 'Al-Nil International Resort',
            'description_ar' => 'وجهة تجمع بين الإقامة المريحة والخدمة الهادئة بتفاصيل مرتبة تناسب الزوار في كل وقت، وتتضمن مساحات مفتوحة وجلسات خارجية هادئة.',
            'description_en' => 'A destination combining comfortable stays with calm, well-arranged service suited to guests at any time, featuring open spaces and quiet outdoor seating areas.',
            'address_ar'     => 'إدلب - معرة النعمان - أوتوستراد حلب-دمشق M5',
            'address_en'     => 'Idlib - Maarat al-Numan - Aleppo-Damascus M5 highway',
            'phone'          => '+963 988 071 777',
            'email'          => 'al_nil_international_resort@email.com',
            'facebook_url'   => 'https://www.facebook.com/100064120894383',
            'instagram_url'  => 'https://www.instagram.com/alnilres/',
            'star_rating'    => 3,
            'is_active'      => true,
            'services'       => ['wifi', 'childcare', 'transport', 'buffet', 'restaurant', 'cafe', 'parking', 'wedding_hall', 'pool', 'service_24h'],
        ],
        [
            'key'            => 'bab_al_hawa_hotel',
            'manager'        => 'manager_idlib2',
            'city_ar'        => 'إدلب',
            'city_en'        => 'Idlib',
            'name_ar'        => 'فندق باب الهوى',
            'name_en'        => 'Bab Al-Hawa Hotel',
            'description_ar' => 'أحد الفنادق البارزة في الشمال السوري، ويمثل نقطة استراحة استراتيجية قريبة من ساحة معبر باب الهوى، مما يجعله مقصدًا للمسافرين.',
            'description_en' => 'One of the prominent hotels in northern Syria, serving as a strategic rest stop near the Bab al-Hawa crossing, making it a destination for travelers.',
            'address_ar'     => 'إدلب - طريق سرمدا - باب الهوى - دوار الصناعة',
            'address_en'     => 'Idlib - Sarmada road - Bab al-Hawa - Industrial roundabout',
            'phone'          => '+963 995 813 863',
            'email'          => 'bab_al_hawa_hotel@email.com',
            'facebook_url'   => 'https://www.facebook.com/p/فندق-باب-الهوى-61587539781602/',
            'instagram_url'  => 'https://www.instagram.com/bab_alhawa_hotel/',
            'star_rating'    => 4,
            'is_active'      => true,
            'services'       => ['room_serv', 'wifi', 'transport', 'buffet', 'restaurant', 'cafe', 'parking', 'meeting_hall', 'service_24h'],
        ],

        // درعا
        [
            'key'            => 'white_rose_hotel',
            'manager'        => 'manager_daraa1',
            'city_ar'        => 'درعا',
            'city_en'        => 'Daraa',
            'name_ar'        => 'فندق وايت روز',
            'name_en'        => 'White Rose Hotel',
            'description_ar' => 'منشأة سياحية وخدمية في مركز مدينة درعا، يعكس الفندق جانبًا من الحياة العمرانية والخدمية في المدينة.',
            'description_en' => 'A tourist and service facility in central Daraa, reflecting a side of the city\'s urban and service life.',
            'address_ar'     => 'درعا - شارع الجمهورية - قرب دوار البريد',
            'address_en'     => 'Daraa - Al-Jumhouriya Street - near the Post Roundabout',
            'phone'          => '+963 981 033 366',
            'email'          => 'white_rose_hotel@email.com',
            'facebook_url'   => '',
            'instagram_url'  => '',
            'star_rating'    => 4,
            'is_active'      => true,
            'services'       => ['gym', 'room_serv', 'wifi', 'transport', 'buffet', 'restaurant', 'cafe', 'parking', 'meeting_hall', 'wedding_hall', 'pool', 'service_24h'],
        ],
    ];

    public function run(): void
    {
        $managers = DemoUsersSeeder::managers();
        $services = $this->services();

        $created = 0;
        $skipped = 0;

        foreach (self::HOTELS as $definition) {
            $manager = $managers->get($definition['manager']);

            if (! $manager) {
                $this->command?->warn("  ! لم يتم العثور على مدير الفندق: {$definition['manager']} ({$definition['name_en']})");
                $skipped++;
                continue;
            }

            $hotel = Hotel::firstOrCreate(
                ['name_en' => $definition['name_en']],
                [
                    'name_ar'        => $definition['name_ar'],
                    'description_ar' => $definition['description_ar'],
                    'description_en' => $definition['description_en'],
                    'city_id'        => $this->city($definition['city_ar'], $definition['city_en'])->id,
                    'address_ar'     => $definition['address_ar'],
                    'address_en'     => $definition['address_en'],
                    'phone'          => $definition['phone'],
                    'email'          => $definition['email'],
                    'facebook_url'   => $definition['facebook_url'],
                    'instagram_url'  => $definition['instagram_url'],
                    'star_rating'    => $definition['star_rating'],
                    'is_active'      => $definition['is_active'],
                    'user_id'        => $manager->id,
                ]
            );

            $hotel->services()->syncWithoutDetaching(
                collect($definition['services'])->map(fn($key) => $services[$key]->id)->all()
            );

            $created++;
        }

        $this->command?->info(sprintf(
            '  ✔ فنادق: %d (متروكة بدون صور عمداً) + %d خدمة%s',
            $created,
            count(self::SERVICES),
            $skipped ? ", تم تخطي {$skipped} بسبب نقص مدير" : ''
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

    /** جلب المدينة بالاسم العربي (فريد بقاعدة البيانات)، وإنشاؤها إن لم تكن موجودة. */
    private function city(string $nameAr, string $nameEn): City
    {
        return City::firstOrCreate(
            ['name_ar' => $nameAr],
            ['name_en' => $nameEn]
        );
    }

    /** الفنادق التجريبية مفهرسة بالـ key. */
    public static function hotels(): \Illuminate\Support\Collection
    {
        $names = array_column(self::HOTELS, 'name_en', 'key');

        $hotels = Hotel::whereIn('name_en', $names)->get()->keyBy('name_en');

        return collect($names)->map(fn($name) => $hotels->get($name))->filter();
    }
}
