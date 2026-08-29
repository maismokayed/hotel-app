<?php

namespace Database\Seeders;

use App\Enums\RoomStatus;
use App\Models\Booking;
use App\Models\Coupon;
use App\Models\Hotel;
use App\Models\Room;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * الحجوزات التجريبية بكل حالاتها:
 * حجوزات ماضية مكتملة، حجز جارٍ حالياً، حجوزات مستقبلية (مؤكدة ومعلّقة)،
 * حجوزات متعددة الغرف، حجوزات بكوبون، ودفع من المحفظة.
 *
 * الثوابت التي يحافظ عليها هذا الـ Seeder (نفس منطق BookingController):
 *  - لا تتداخل حجوزات غير ملغاة على نفس الغرفة في نفس الفترة.
 *  - total_price = عدد الليالي × مجموع أسعار الغرف.
 *  - final_price = total_price - discount_amount.
 *  - الدفع من المحفظة يخصم الرصيد ويسجّل حركة debit/payment،
 *    والإلغاء المبكر يعيد المبلغ بحركة credit/refund.
 *  - used_count للكوبون = عدد الحجوزات غير الملغاة التي استخدمته.
 */
class DemoBookingsSeeder extends Seeder
{
    /** وقت الدخول والخروج (نفس الساعة حتى يكون عدد الليالي عدداً صحيحاً). */
    private const CHECK_HOUR = 14;

    /**
     * سيناريوهات الحجز المطبّقة على كل فندق فعّال.
     * offset: بداية الحجز بالأيام نسبةً لليوم (سالب = الماضي).
     */
    private const SCENARIOS = [
        // ── حجوزات ماضية مكتملة ──────────────────────────────────────────
        ['kind' => 'completed', 'offset' => -300, 'nights' => 3, 'rooms' => ['double' => 1],               'payment' => 'wallet', 'coupon' => null],
        ['kind' => 'completed', 'offset' => -240, 'nights' => 2, 'rooms' => ['single' => 1],               'payment' => 'cash',   'coupon' => null],
        ['kind' => 'completed', 'offset' => -180, 'nights' => 5, 'rooms' => ['double' => 2],               'payment' => 'wallet', 'coupon' => 'WELCOME10'],
        ['kind' => 'completed', 'offset' => -120, 'nights' => 4, 'rooms' => ['suite'  => 1],               'payment' => 'cash',   'coupon' => 'FLAT50'],
        ['kind' => 'completed', 'offset' => -60,  'nights' => 2, 'rooms' => ['double' => 1, 'single' => 1], 'payment' => 'wallet', 'coupon' => null],
        ['kind' => 'completed', 'offset' => -25,  'nights' => 3, 'rooms' => ['deluxe' => 1],               'payment' => 'wallet', 'coupon' => 'LOYAL5'],

        // ── حجز ملغى (إلغاء مبكر، بدون غرامة) ────────────────────────────
        ['kind' => 'cancelled', 'offset' => -45,  'nights' => 2, 'rooms' => ['double' => 1],               'payment' => 'wallet', 'coupon' => null],

        // ── إقامة جارية حالياً ───────────────────────────────────────────
        ['kind' => 'in_stay',   'offset' => -2,   'nights' => 5, 'rooms' => ['double' => 1],               'payment' => 'wallet', 'coupon' => null],

        // ── حجوزات مستقبلية ─────────────────────────────────────────────
        ['kind' => 'confirmed', 'offset' => 7,    'nights' => 3, 'rooms' => ['suite'  => 1],               'payment' => 'wallet', 'coupon' => 'SUMMER25'],
        ['kind' => 'pending',   'offset' => 16,   'nights' => 2, 'rooms' => ['double' => 1],               'payment' => 'cash',   'coupon' => null],
        ['kind' => 'pending',   'offset' => 30,   'nights' => 4, 'rooms' => ['double' => 2, 'suite' => 1],  'payment' => 'cash',   'coupon' => 'WELCOME10'],
        ['kind' => 'confirmed', 'offset' => 45,   'nights' => 2, 'rooms' => ['single' => 1],               'payment' => 'wallet', 'coupon' => null],
    ];

    /** أسماء ضيوف (عندما يحجز المستخدم لشخص آخر). */
    private const GUESTS = [
        ['name' => 'وسيم العبدالله', 'phone' => '0944110022'],
        ['name' => 'هبة الشامي',     'phone' => '0944110033'],
        ['name' => 'فادي المير',     'phone' => '0944110044'],
    ];

    /** فترات الإشغال المحجوزة: [room_id => [[start, end], ...]] */
    private array $occupied = [];

    /** عدد مرات استخدام كل كوبون (غير الملغاة). */
    private array $couponUses = [];

    /** مبالغ الإرجاع المؤجلة (تُطبّق بتاريخها ضمن التسلسل الزمني). */
    private array $pendingRefunds = [];

    private array $stats = [
        'completed' => 0,
        'confirmed' => 0,
        'pending'   => 0,
        'cancelled' => 0,
        'multiRoom' => 0,
        'withCoupon' => 0,
        'wallet'    => 0,
        'skipped'   => 0,
    ];

    public function run(): void
    {
        $users = DemoUsersSeeder::normalUsers()->values();

        if ($users->isEmpty()) {
            $this->command?->warn('  ! لا يوجد مستخدمون تجريبيون — تم تخطي الحجوزات.');
            return;
        }

        if (Booking::whereIn('user_id', $users->pluck('id'))->exists()) {
            $this->command?->warn('  ! يوجد حجوزات تجريبية مسبقاً — تم تخطي DemoBookingsSeeder.');
            return;
        }

        $hotels  = DemoHotelsSeeder::hotels()->filter(fn (Hotel $hotel) => $hotel->is_active)->values();
        $coupons = DemoCouponsSeeder::usable()->keyBy('code');
        $wallets = Wallet::whereIn('user_id', $users->pluck('id'))->get()->keyBy('user_id');

        $cursor = 0;
        $plans  = [];

        foreach ($hotels as $hotelIndex => $hotel) {
            $rooms = $hotel->rooms()
                ->where('status', RoomStatus::AVAILABLE->value)
                ->orderBy('id')
                ->get();

            if ($rooms->isEmpty()) {
                continue;
            }

            foreach (self::SCENARIOS as $scenarioIndex => $scenario) {
                $user = $users[$cursor % $users->count()];
                $cursor++;

                // إزاحة بسيطة لكل فندق حتى لا تتكرر نفس التواريخ،
                // مع إبقاء الإقامة الجارية داخل فترتها في كل الفنادق.
                $shift = $scenario['kind'] === 'in_stay' ? -($hotelIndex % 3) : $hotelIndex * 3;

                $checkIn = now()->addDays($scenario['offset'] + $shift)->setTime(self::CHECK_HOUR, 0);

                $plans[] = [
                    'hotel'     => $hotel,
                    'rooms'     => $rooms,
                    'user'      => $user,
                    'wallet'    => $wallets->get($user->id),
                    'scenario'  => $scenario,
                    'coupon'    => $coupons->get($scenario['coupon']),
                    'index'     => $scenarioIndex,
                    'checkIn'   => $checkIn,
                    'checkOut'  => $checkIn->copy()->addDays($scenario['nights'])->setTime(self::CHECK_HOUR, 0),
                    'createdAt' => $this->createdAt($checkIn, $scenarioIndex),
                ];
            }
        }

        // ترتيب زمني حسب تاريخ إنشاء الحجز حتى تبقى حركات المحفظة منطقية
        // (لا يمكن أن يُدفع من رصيد لم يُودَع بعد).
        usort($plans, fn (array $a, array $b) => $a['createdAt'] <=> $b['createdAt']);

        foreach ($plans as $plan) {
            $this->flushRefunds($plan['createdAt']);
            $this->createBooking($plan);
        }

        $this->flushRefunds(null);

        $this->syncCouponUsage($coupons);
        $this->markRoomsInStay();

        $this->command?->info(sprintf(
            '  ✔ حجوزات: %d مكتمل، %d مؤكد، %d معلّق، %d ملغى | %d متعدد الغرف، %d بكوبون، %d دفع بالمحفظة%s',
            $this->stats['completed'],
            $this->stats['confirmed'],
            $this->stats['pending'],
            $this->stats['cancelled'],
            $this->stats['multiRoom'],
            $this->stats['withCoupon'],
            $this->stats['wallet'],
            $this->stats['skipped'] ? ' | تم تخطي ' . $this->stats['skipped'] . ' لعدم توفر غرف' : ''
        ));
    }

    private function createBooking(array $plan): void
    {
        /** @var Hotel $hotel */
        $hotel = $plan['hotel'];
        /** @var User $user */
        $user = $plan['user'];
        /** @var Wallet|null $wallet */
        $wallet = $plan['wallet'];
        /** @var Coupon|null $coupon */
        $coupon = $plan['coupon'];

        $scenario      = $plan['scenario'];
        $scenarioIndex = $plan['index'];
        $checkIn       = $plan['checkIn'];
        $checkOut      = $plan['checkOut'];
        $createdAt     = $plan['createdAt'];

        $selected = $this->pickRooms($plan['rooms'], $scenario['rooms'], $checkIn, $checkOut);

        if ($selected->isEmpty()) {
            $this->stats['skipped']++;
            return;
        }

        $nights     = $scenario['nights'];
        $totalPrice = round($selected->sum(fn (Room $room) => $nights * (float) $room->price_per_night), 2);

        $discount = $coupon ? $this->discount($coupon, $totalPrice) : 0.0;
        $final    = round(max(0, $totalPrice - $discount), 2);

        // الدفع من المحفظة يحتاج رصيداً كافياً، وإلا يتحول الحجز إلى دفع نقدي.
        $payment = $scenario['payment'];

        if ($payment === 'wallet' && (! $wallet || (float) $wallet->balance < $final)) {
            $payment = 'cash';
        }

        $status = match ($scenario['kind']) {
            'completed' => 'completed',
            'cancelled' => 'cancelled',
            'in_stay'   => 'confirmed',
            'confirmed' => 'confirmed',
            default     => 'pending',
        };

        $capacity = (int) $selected->sum('capacity');
        $guest    = $this->guest($user, $scenarioIndex);

        $booking = Booking::create([
            'user_id'          => $user->id,
            'hotel_id'         => $hotel->id,
            'coupon_id'        => $coupon?->id,
            'guest_full_name'  => $guest['name'],
            'guest_phone'      => $guest['phone'],
            'check_in_date'    => $checkIn,
            'check_out_date'   => $checkOut,
            'status'           => $status,
            'total_price'      => $totalPrice,
            'discount_amount'  => round($discount, 2),
            'final_price'      => $final,
            'number_of_guests' => max(1, $capacity - ($scenarioIndex % 2)),
            'payment_method'   => $payment,
        ]);

        $booking->rooms()->attach(
            $selected->pluck('id')->all(),
            ['created_at' => $createdAt, 'updated_at' => $createdAt]
        );

        $updatedAt = match ($status) {
            'completed' => $checkOut,
            'cancelled' => $this->cancelledAt($createdAt, $checkIn),
            default     => $createdAt,
        };

        $booking->forceFill(['created_at' => $createdAt, 'updated_at' => $updatedAt])->saveQuietly();

        // حجز الفترة على الغرف حتى لا يتداخل معها حجز لاحق.
        if ($status !== 'cancelled') {
            foreach ($selected as $room) {
                $this->occupied[$room->id][] = [$checkIn, $checkOut];
            }
        }

        $this->settlePayment($booking, $wallet, $payment, $final, $createdAt, $updatedAt, $status);

        if ($coupon && $status !== 'cancelled') {
            $this->couponUses[$coupon->id] = ($this->couponUses[$coupon->id] ?? 0) + 1;
            $this->stats['withCoupon']++;
        }

        if ($selected->count() > 1) {
            $this->stats['multiRoom']++;
        }

        $this->stats[$status]++;
    }

    /**
     * اختيار غرف متاحة حسب النوع المطلوب، مع الرجوع لأي نوع آخر
     * إذا لم يتوفر العدد المطلوب من النوع المحدد في هذا الفندق.
     */
    private function pickRooms(Collection $rooms, array $request, Carbon $checkIn, Carbon $checkOut): Collection
    {
        $selected = collect();

        foreach ($request as $type => $quantity) {
            $free = $rooms
                ->filter(fn (Room $room) => $room->type->value === $type)
                ->reject(fn (Room $room) => $selected->contains('id', $room->id))
                ->filter(fn (Room $room) => $this->isFree($room, $checkIn, $checkOut))
                ->take($quantity);

            $selected = $selected->merge($free);

            $missing = $quantity - $free->count();

            if ($missing > 0) {
                $fallback = $rooms
                    ->reject(fn (Room $room) => $selected->contains('id', $room->id))
                    ->filter(fn (Room $room) => $this->isFree($room, $checkIn, $checkOut))
                    ->take($missing);

                $selected = $selected->merge($fallback);
            }
        }

        return $selected->values();
    }

    /** هل الغرفة خالية في هذه الفترة؟ */
    private function isFree(Room $room, Carbon $checkIn, Carbon $checkOut): bool
    {
        foreach ($this->occupied[$room->id] ?? [] as [$start, $end]) {
            if ($checkIn->lt($end) && $checkOut->gt($start)) {
                return false;
            }
        }

        return true;
    }

    /** قيمة الخصم حسب نوع الكوبون. */
    private function discount(Coupon $coupon, float $totalPrice): float
    {
        return $coupon->discount_type === 'percentage'
            ? round($totalPrice * ((float) $coupon->discount_value / 100), 2)
            : min((float) $coupon->discount_value, $totalPrice);
    }

    /** تاريخ إنشاء الحجز: قبل موعد الوصول بأيام، وليس في المستقبل. */
    private function createdAt(Carbon $checkIn, int $scenarioIndex): Carbon
    {
        $createdAt = $checkIn->copy()->subDays(5 + ($scenarioIndex % 12))->setTime(10, 30);

        if ($createdAt->isFuture()) {
            $createdAt = now()->subDays(($scenarioIndex % 5) + 1)->setTime(10, 30);
        }

        return $createdAt;
    }

    /** تاريخ الإلغاء: بعد الإنشاء وقبل موعد الوصول بأكثر من 3 أيام (إلغاء مبكر بلا غرامة). */
    private function cancelledAt(Carbon $createdAt, Carbon $checkIn): Carbon
    {
        $cancelledAt = $createdAt->copy()->addDay()->setTime(17, 0);
        $deadline    = $checkIn->copy()->subDays(4);

        return $cancelledAt->gt($deadline) ? $deadline : $cancelledAt;
    }

    /** حركات المحفظة: خصم عند الدفع، وإرجاع كامل عند الإلغاء المبكر. */
    private function settlePayment(
        Booking $booking,
        ?Wallet $wallet,
        string $payment,
        float $final,
        Carbon $createdAt,
        Carbon $updatedAt,
        string $status
    ): void {
        if ($payment !== 'wallet' || ! $wallet) {
            return;
        }

        $wallet->decrement('balance', $final);
        $this->transaction($wallet, $final, 'debit', 'payment', $createdAt);

        $this->stats['wallet']++;

        // الإلغاء المبكر يرجّع كامل المبلغ، لكن بتاريخ الإلغاء لا بتاريخ الحجز.
        if ($status === 'cancelled') {
            $this->pendingRefunds[] = ['wallet' => $wallet, 'amount' => $final, 'at' => $updatedAt];
        }
    }

    /**
     * تنفيذ عمليات الإرجاع التي حان تاريخها (أو كلها عند تمرير null)
     * حتى لا يظهر رصيد في المحفظة قبل أوانه.
     */
    private function flushRefunds(?Carbon $until): void
    {
        foreach ($this->pendingRefunds as $key => $refund) {
            if ($until !== null && $refund['at']->gt($until)) {
                continue;
            }

            $refund['wallet']->increment('balance', $refund['amount']);
            $this->transaction($refund['wallet'], $refund['amount'], 'credit', 'refund', $refund['at']);

            unset($this->pendingRefunds[$key]);
        }
    }

    private function transaction(Wallet $wallet, float $amount, string $type, string $reason, Carbon $at): void
    {
        WalletTransaction::create([
            'wallet_id'        => $wallet->id,
            'user_id'          => $wallet->user_id,
            'amount'           => $amount,
            'transaction_type' => $type,
            'reason'           => $reason,
            'transaction_date' => $at,
        ])->forceFill(['created_at' => $at, 'updated_at' => $at])->saveQuietly();
    }

    /** اسم الضيف: المستخدم نفسه غالباً، وأحياناً شخص آخر. */
    private function guest(User $user, int $scenarioIndex): array
    {
        if ($scenarioIndex % 4 !== 3) {
            return ['name' => $user->full_name, 'phone' => $user->phone];
        }

        $guest = self::GUESTS[$scenarioIndex % count(self::GUESTS)];

        return ['name' => $guest['name'], 'phone' => $guest['phone']];
    }

    /** ضبط used_count لكل كوبون حسب الحجوزات غير الملغاة. */
    private function syncCouponUsage(Collection $coupons): void
    {
        foreach ($coupons as $coupon) {
            $coupon->update(['used_count' => $this->couponUses[$coupon->id] ?? 0]);
        }
    }

    /** الغرف التي فيها إقامة جارية الآن تصبح بحالة "محجوزة". */
    private function markRoomsInStay(): void
    {
        $roomIds = Room::whereHas('bookings', function ($query) {
            $query->whereIn('status', ['confirmed', 'pending'])
                ->where('check_in_date', '<=', now())
                ->where('check_out_date', '>', now());
        })->pluck('id');

        if ($roomIds->isNotEmpty()) {
            Room::whereIn('id', $roomIds)->update(['status' => RoomStatus::BOOKED->value]);
        }
    }
}
