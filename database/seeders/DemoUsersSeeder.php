<?php

namespace Database\Seeders;

use App\Enums\RoleEnum;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * مستخدمو البيانات التجريبية: مدير عام + مدراء فنادق + مستخدمون عاديون.
 *
 * كل المستخدمين هنا بريدهم ينتهي بـ DemoDataSeeder::EMAIL_DOMAIN
 * وهذا هو المعرّف الوحيد الذي تعتمد عليه باقي الـ Demo Seeders.
 */
class DemoUsersSeeder extends Seeder
{
    /** المدير العام للنظام. */
    public const ADMIN = [
        'full_name' => 'رنا الحلبي',
        'email'     => 'admin@demo.test',
        'phone'     => '0930000001',
    ];

    /** مدراء الفنادق (كل مدير يملك فندقاً أو أكثر). */
    public const MANAGERS = [
        ['key' => 'manager_sham',    'full_name' => 'سامر الخطيب',   'email' => 'manager.sham@demo.test',    'phone' => '0931000001'],
        ['key' => 'manager_halab',   'full_name' => 'ليلى العيسى',   'email' => 'manager.halab@demo.test',   'phone' => '0931000002'],
        ['key' => 'manager_sahel',   'full_name' => 'باسل الأحمد',   'email' => 'manager.sahel@demo.test',   'phone' => '0931000003'],
        ['key' => 'manager_wasat',   'full_name' => 'هناء المصري',   'email' => 'manager.wasat@demo.test',   'phone' => '0931000004'],
    ];

    /** المستخدمون العاديون (أصحاب الحجوزات والتقييمات). */
    public const USERS = [
        ['full_name' => 'أحمد سعيد',      'email' => 'ahmad@demo.test',   'phone' => '0932000001'],
        ['full_name' => 'مريم الحسن',     'email' => 'mariam@demo.test',  'phone' => '0932000002'],
        ['full_name' => 'عمر الشيخ',      'email' => 'omar@demo.test',    'phone' => '0932000003'],
        ['full_name' => 'نور الدين قاسم', 'email' => 'nour@demo.test',    'phone' => '0932000004'],
        ['full_name' => 'سلمى برهان',     'email' => 'salma@demo.test',   'phone' => '0932000005'],
        ['full_name' => 'خالد العلي',     'email' => 'khaled@demo.test',  'phone' => '0932000006'],
        ['full_name' => 'ريم الجندي',     'email' => 'reem@demo.test',    'phone' => '0932000007'],
        ['full_name' => 'يوسف حمدان',     'email' => 'yousef@demo.test',  'phone' => '0932000008'],
        ['full_name' => 'دانا الرفاعي',   'email' => 'dana@demo.test',    'phone' => '0932000009'],
        ['full_name' => 'زياد النجار',    'email' => 'ziad@demo.test',    'phone' => '0932000010'],
        ['full_name' => 'لمى الصباغ',     'email' => 'lama@demo.test',    'phone' => '0932000011'],
        ['full_name' => 'طارق الحموي',    'email' => 'tarek@demo.test',   'phone' => '0932000012'],
    ];

    public function run(): void
    {
        $this->createUser(self::ADMIN, RoleEnum::ADMIN->value);

        foreach (self::MANAGERS as $manager) {
            $this->createUser($manager, RoleEnum::MANAGER->value);
        }

        foreach (self::USERS as $user) {
            $this->createUser($user, RoleEnum::USER->value);
        }

        $this->command?->info(sprintf(
            '  ✔ مستخدمون: 1 أدمن + %d مدير + %d مستخدم (كلمة المرور: %s)',
            count(self::MANAGERS),
            count(self::USERS),
            DemoDataSeeder::PASSWORD
        ));
    }

    private function createUser(array $data, string $role): User
    {
        $user = User::firstOrCreate(
            ['email' => $data['email']],
            [
                'full_name' => $data['full_name'],
                'phone'     => $data['phone'],
                'password'  => Hash::make(DemoDataSeeder::PASSWORD),
            ]
        );

        if ($user->wasRecentlyCreated) {
            $user->forceFill(['email_verified_at' => now()])->save();
        }

        if (! $user->hasRole($role)) {
            $user->assignRole($role);
        }

        return $user;
    }

    /** مدراء الفنادق التجريبيون مفهرسين بالـ key المستخدم في DemoHotelsSeeder. */
    public static function managers(): \Illuminate\Support\Collection
    {
        $emails = array_column(self::MANAGERS, 'email', 'key');

        $managers = User::whereIn('email', $emails)->get()->keyBy('email');

        return collect($emails)->map(fn ($email) => $managers->get($email))->filter();
    }

    /** المستخدمون العاديون التجريبيون. */
    public static function normalUsers(): \Illuminate\Support\Collection
    {
        return User::whereIn('email', array_column(self::USERS, 'email'))
            ->orderBy('id')
            ->get();
    }

    /** كل المستخدمين التجريبيين (أدمن + مدراء + مستخدمون). */
    public static function all(): \Illuminate\Support\Collection
    {
        return User::where('email', 'like', '%' . DemoDataSeeder::EMAIL_DOMAIN)
            ->orderBy('id')
            ->get();
    }
}
