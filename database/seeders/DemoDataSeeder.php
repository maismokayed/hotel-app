<?php

namespace Database\Seeders;

use App\Models\Booking;
use App\Models\City;
use App\Models\Hotel;
use App\Models\Review;
use App\Models\Room;
use App\Models\User;
use App\Models\WalletTransaction;
use Illuminate\Database\Seeder;
use Spatie\Permission\PermissionRegistrar;

/**
 * بيانات تجريبية متكاملة لعرض التطبيق (Demo Data).
 *
 *   php artisan db:seed --class=DemoDataSeeder
 *   # أو من الصفر:
 *   php artisan migrate:fresh --seed && php artisan db:seed --class=DemoDataSeeder
 *
 * الترتيب مهم لأن كل Seeder يعتمد على الذي قبله:
 *
 *   DemoDataSeeder
 *   ├── DemoUsersSeeder      (أدمن + مدراء + مستخدمون)
 *   ├── DemoHotelsSeeder     (فنادق + خدمات + صور لكل فندق)
 *   ├── DemoRoomsSeeder      (غرف + صورة لكل غرفة)
 *   ├── DemoCouponsSeeder    (كوبونات صالحة/منتهية/معطّلة)
 *   ├── DemoWalletsSeeder    (محافظ + إيداعات أولية)
 *   ├── DemoBookingsSeeder   (ماضية/حالية/مستقبلية، متعددة الغرف، بكوبون، دفع بالمحفظة)
 *   └── DemoReviewsSeeder    (تقييمات للحجوزات المكتملة فقط)
 *
 * كل الـ Seeders قابلة لإعادة التشغيل بأمان (لا تكرّر البيانات).
 */
class DemoDataSeeder extends Seeder
{
    /** كلمة المرور الموحّدة لكل حسابات الـ Demo. */
    public const PASSWORD = 'password';

    /** نطاق البريد الذي يميّز بيانات الـ Demo عن غيرها. */
    public const EMAIL_DOMAIN = '@demo.test';

    public function run(): void
    {
        $this->prepare();

        $this->command?->info('▶ بدء إدخال البيانات التجريبية...');

        $this->call([
            DemoUsersSeeder::class,
            DemoHotelsSeeder::class,
            DemoRoomsSeeder::class,
            DemoCouponsSeeder::class,
            DemoWalletsSeeder::class,
            DemoBookingsSeeder::class,
            DemoReviewsSeeder::class,
        ]);

        $this->summary();
    }

    /** التأكد من وجود المتطلبات الأساسية: الأدوار والصلاحيات والمدن. */
    private function prepare(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->call(RoleSeeder::class);
        $this->call(RolePermissionSeeder::class);

        if (City::count() === 0) {
            $this->call(CitySeeder::class);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /** ملخّص نهائي لما تم إدخاله. */
    private function summary(): void
    {
        if (! $this->command) {
            return;
        }

        $userIds  = DemoUsersSeeder::all()->pluck('id');
        $hotelIds = DemoHotelsSeeder::hotels()->pluck('id');

        $this->command->newLine();
        $this->command->info('✅ تم إدخال البيانات التجريبية بنجاح');
        $this->command->table(
            ['العنصر', 'العدد'],
            [
                ['المستخدمون',        User::whereIn('id', $userIds)->count()],
                ['الفنادق',           $hotelIds->count()],
                ['صور الفنادق',       Hotel::whereIn('id', $hotelIds)->get()->sum(fn ($hotel) => $hotel->getMedia('images')->count())],
                ['الغرف',             Room::whereIn('hotel_id', $hotelIds)->count()],
                ['الحجوزات',          Booking::whereIn('user_id', $userIds)->count()],
                ['حركات المحفظة',     WalletTransaction::whereIn('user_id', $userIds)->count()],
                ['التقييمات',         Review::whereIn('user_id', $userIds)->count()],
            ]
        );

        $this->command->newLine();
        $this->command->line('  حسابات الدخول (كلمة المرور للجميع: <fg=yellow>' . self::PASSWORD . '</>)');
        $this->command->line('    أدمن   : <fg=green>' . DemoUsersSeeder::ADMIN['email'] . '</>');
        $this->command->line('    مدير   : <fg=green>' . DemoUsersSeeder::MANAGERS[0]['email'] . '</>');
        $this->command->line('    مستخدم : <fg=green>' . DemoUsersSeeder::USERS[0]['email'] . '</>');
        $this->command->newLine();
    }
}
