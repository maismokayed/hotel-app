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
 *
 * كل دور له كلمة مرور موحّدة خاصة به (وليست كلمة مرور واحدة للجميع):
 *   أدمن  -> password
 *   مدير  -> password1
 *   مستخدم -> password2
 */
class DemoUsersSeeder extends Seeder
{
    /** كلمة مرور كل دور. */
    public const PASSWORDS = [
        'admin'   => 'password',
        'manager' => 'password1',
        'user'    => 'password2',
    ];

    /** المدير العام للنظام. */
    public const ADMIN = [
        'full_name' => 'رنا الحلبي',
        'email'     => 'admin@demo.test',
        'phone'     => '0930000001',
    ];

    /**
     * مدراء الفنادق.
     * معظم المدراء يدير فندقاً واحداً، وبعضهم فقط (حلب، اللاذقية، طرطوس) يدير فندقين
     * لتفادي تضخّم عدد المدراء، بما يتناسب مع 29 فندقاً فعلياً (حسب hotels_v2.json،
     * وبعد استبعاد فنادق "ريف دمشق" نهائياً وإضافة "درعا" كمدينة جديدة):
     *   - 3 مدراء كل واحد منهم يدير فندقين (حلب، اللاذقية، طرطوس) = 6 فنادق
     *   - 23 مديراً كل واحد منهم يدير فندقاً واحداً = 23 فندق
     */
    public const MANAGERS = [
        // دمشق (6 فنادق: كل فندق بمدير مستقل - ما عاد فيه زوج هون)
        ['key' => 'manager_sham1', 'full_name' => 'سامر الخطيب',  'email' => 'manager.sham1@demo.test', 'phone' => '0931000001'],
        ['key' => 'manager_sham2', 'full_name' => 'غيث النابلسي', 'email' => 'manager.sham2@demo.test', 'phone' => '0931000002'],
        ['key' => 'manager_sham3', 'full_name' => 'رهف السقا',    'email' => 'manager.sham3@demo.test', 'phone' => '0931000003'],
        ['key' => 'manager_sham4', 'full_name' => 'كنان الزعبي',  'email' => 'manager.sham4@demo.test', 'phone' => '0931000004'],
        ['key' => 'manager_sham5', 'full_name' => 'يارا الحكيم',  'email' => 'manager.sham5@demo.test', 'phone' => '0931000005'],
        ['key' => 'manager_sham6', 'full_name' => 'فادي شعبان',   'email' => 'manager.sham6@demo.test', 'phone' => '0931000006'],

        // حلب (5 فنادق: مدير واحد لفندقين + 3 مدراء لفندق واحد)
        ['key' => 'manager_halab',  'full_name' => 'ليلى العيسى',  'email' => 'manager.halab@demo.test',  'phone' => '0931000007'],
        ['key' => 'manager_halab2', 'full_name' => 'فراس قباني',   'email' => 'manager.halab2@demo.test', 'phone' => '0931000008'],
        ['key' => 'manager_halab3', 'full_name' => 'هبة الميداني', 'email' => 'manager.halab3@demo.test', 'phone' => '0931000009'],
        ['key' => 'manager_halab4', 'full_name' => 'ريم الأتاسي',  'email' => 'manager.halab4@demo.test', 'phone' => '0931000010'],

        // اللاذقية (4 فنادق: مدير واحد لفندقين + مديران لفندق واحد)
        ['key' => 'manager_latakia',  'full_name' => 'باسل الأحمد', 'email' => 'manager.latakia@demo.test',  'phone' => '0931000011'],
        ['key' => 'manager_latakia2', 'full_name' => 'ديما شاهين',  'email' => 'manager.latakia2@demo.test', 'phone' => '0931000012'],
        ['key' => 'manager_latakia3', 'full_name' => 'وسيم الحلاق', 'email' => 'manager.latakia3@demo.test', 'phone' => '0931000013'],

        // طرطوس (4 فنادق: مدير واحد لفندقين + مديران لفندق واحد)
        ['key' => 'manager_tartus',  'full_name' => 'نغم فارس',  'email' => 'manager.tartus@demo.test',  'phone' => '0931000014'],
        ['key' => 'manager_tartus2', 'full_name' => 'أيمن سلوم', 'email' => 'manager.tartus2@demo.test', 'phone' => '0931000015'],
        ['key' => 'manager_tartus3', 'full_name' => 'لؤي دياب',  'email' => 'manager.tartus3@demo.test', 'phone' => '0931000016'],

        // حمص (فندقان: كل فندق بمدير مستقل)
        ['key' => 'manager_wasat', 'full_name' => 'هناء المصري', 'email' => 'manager.wasat@demo.test', 'phone' => '0931000017'],
        ['key' => 'manager_homs2', 'full_name' => 'جهاد النقري', 'email' => 'manager.homs2@demo.test', 'phone' => '0931000018'],

        // حماة (فندقان: كل فندق بمدير مستقل)
        ['key' => 'manager_hama1', 'full_name' => 'مازن العبود',   'email' => 'manager.hama1@demo.test', 'phone' => '0931000019'],
        ['key' => 'manager_hama2', 'full_name' => 'سوسن الحوراني', 'email' => 'manager.hama2@demo.test', 'phone' => '0931000020'],

        // دير الزور (فندق واحد فقط: مدير مستقل)
        ['key' => 'manager_deir1', 'full_name' => 'عبير الجاسم', 'email' => 'manager.deir1@demo.test', 'phone' => '0931000021'],

        // الرقة (فندقان: كل فندق بمدير مستقل)
        ['key' => 'manager_raqqa1', 'full_name' => 'منال الأسعد', 'email' => 'manager.raqqa1@demo.test', 'phone' => '0931000022'],
        ['key' => 'manager_raqqa2', 'full_name' => 'طلال الحسين', 'email' => 'manager.raqqa2@demo.test', 'phone' => '0931000023'],

        // إدلب (فندقان: كل فندق بمدير مستقل)
        ['key' => 'manager_idlib1', 'full_name' => 'لينا شحادة',   'email' => 'manager.idlib1@demo.test', 'phone' => '0931000024'],
        ['key' => 'manager_idlib2', 'full_name' => 'عدنان الحموي', 'email' => 'manager.idlib2@demo.test', 'phone' => '0931000025'],

        // درعا (فندق واحد فقط - مدينة جديدة كلياً: مدير مستقل)
        ['key' => 'manager_daraa1', 'full_name' => 'نزار المحاميد', 'email' => 'manager.daraa1@demo.test', 'phone' => '0931000026'],
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
        ['full_name' => 'هلا منصور',      'email' => 'hala@demo.test',    'phone' => '0932000013'],
        ['full_name' => 'كريم دياب',      'email' => 'karim@demo.test',   'phone' => '0932000014'],
        ['full_name' => 'رغد العمر',      'email' => 'raghad@demo.test',  'phone' => '0932000015'],
        ['full_name' => 'باسم شحود',      'email' => 'basem@demo.test',   'phone' => '0932000016'],
        ['full_name' => 'إيمان الخوري',   'email' => 'eman@demo.test',    'phone' => '0932000017'],
        ['full_name' => 'مازن فتال',      'email' => 'mazen@demo.test',   'phone' => '0932000018'],
        ['full_name' => 'شذى القاضي',     'email' => 'shatha@demo.test',  'phone' => '0932000019'],
        ['full_name' => 'وائل بيطار',     'email' => 'wael@demo.test',    'phone' => '0932000020'],
        ['full_name' => 'جود الأسود',     'email' => 'joud@demo.test',    'phone' => '0932000021'],
        ['full_name' => 'ليان صالح',      'email' => 'layan@demo.test',   'phone' => '0932000022'],
        ['full_name' => 'حسام المغربي',   'email' => 'hussam@demo.test',  'phone' => '0932000023'],
        ['full_name' => 'رنا شمس الدين',  'email' => 'rana@demo.test',    'phone' => '0932000024'],
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
            '  ✔ مستخدمون: 1 أدمن (كلمة المرور: %s) + %d مدير (كلمة المرور: %s) + %d مستخدم (كلمة المرور: %s)',
            self::PASSWORDS['admin'],
            count(self::MANAGERS),
            self::PASSWORDS['manager'],
            count(self::USERS),
            self::PASSWORDS['user']
        ));
    }

    private function createUser(array $data, string $role): User
    {
        $user = User::firstOrCreate(
            ['email' => $data['email']],
            [
                'full_name' => $data['full_name'],
                'phone'     => $data['phone'],
                'password'  => Hash::make(self::PASSWORDS[$role] ?? DemoDataSeeder::PASSWORD),
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

        return collect($emails)->map(fn($email) => $managers->get($email))->filter();
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
