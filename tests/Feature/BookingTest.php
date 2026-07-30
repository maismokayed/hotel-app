<?php

use App\Models\User;
use App\Models\Room;
use App\Models\Hotel;
use App\Models\Booking;
use App\Models\Coupon;
use App\Models\Wallet;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

uses(RefreshDatabase::class);

beforeEach(function () {
    Carbon::setTestNow(Carbon::parse('2026-01-01 10:00:00'));

    app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    $this->seed(RoleSeeder::class);

    $this->user = User::factory()->create();
    $this->user->assignRole('user');

    $this->hotel = Hotel::factory()->create(['is_active' => true]);
    $this->room = Room::factory()->create([
        'hotel_id'        => $this->hotel->id,
        'type'            => 'single',
        'price_per_night' => 100,
        'status'          => 'available',
    ]);
});

afterEach(function () {
    Carbon::setTestNow();
});

// ============================================================
// STORE TESTS
// ============================================================

it('can create a booking successfully', function () {
    $response = $this->actingAs($this->user)
        ->postJson('/api/bookings', [
            'guest_full_name'  => 'Test Guest',
            'guest_phone'      => '0999999999',
            'hotel_id'         => $this->hotel->id,
            'rooms'            => [
                ['type' => 'single', 'quantity' => 1],
            ],
            'check_in_date'    => now()->addDays(2)->format('Y-m-d'),
            'check_out_date'   => now()->addDays(5)->format('Y-m-d'),
            'number_of_guests' => 2,
            'payment_method'   => 'cash',
        ]);

    $response->assertStatus(201)
        ->assertJsonStructure([
            'data' => [
                'id',
                'hotel',
                'rooms',
                'user',
                'check_in_date',
                'check_out_date',
                'status',
                'total_price',
                'final_price'
            ]
        ])
        ->assertJsonPath('data.status', 'pending');

    $this->assertDatabaseHas('bookings', [
        'user_id'  => $this->user->id,
        'hotel_id' => $this->hotel->id,
        'status'   => 'pending',
    ]);

    $this->assertDatabaseHas('booking_room', [
        'room_id' => $this->room->id,
    ]);
});

it('confirms a wallet-paid booking immediately and debits the wallet', function () {
    $wallet = Wallet::factory()->create([
        'user_id' => $this->user->id,
        'balance' => 1000,
    ]);

    $response = $this->actingAs($this->user)
        ->postJson('/api/bookings', [
            'guest_full_name'  => 'Test Guest',
            'guest_phone'      => '0999999999',
            'hotel_id'         => $this->hotel->id,
            'rooms'            => [
                ['type' => 'single', 'quantity' => 1],
            ],
            'check_in_date'    => now()->addDays(5)->format('Y-m-d'),
            'check_out_date'   => now()->addDays(8)->format('Y-m-d'), // 3 nights x 100
            'number_of_guests' => 2,
            'payment_method'   => 'wallet',
        ]);

    $response->assertStatus(201)
        ->assertJsonPath('data.status', 'confirmed');

    expect((float) $wallet->fresh()->balance)->toBe(700.00);

    $this->assertDatabaseHas('wallet_transactions', [
        'wallet_id'        => $wallet->id,
        'amount'           => 300,
        'transaction_type' => 'debit',
    ]);
});

it('rejects a wallet booking when the wallet balance is insufficient', function () {
    Wallet::factory()->create([
        'user_id' => $this->user->id,
        'balance' => 1,
    ]);

    $response = $this->actingAs($this->user)
        ->postJson('/api/bookings', [
            'guest_full_name'  => 'Test Guest',
            'guest_phone'      => '0999999999',
            'hotel_id'         => $this->hotel->id,
            'rooms'            => [
                ['type' => 'single', 'quantity' => 1],
            ],
            'check_in_date'    => now()->addDays(2)->format('Y-m-d'),
            'check_out_date'   => now()->addDays(4)->format('Y-m-d'),
            'number_of_guests' => 2,
            'payment_method'   => 'wallet',
        ]);

    $response->assertStatus(422)
        ->assertJsonPath('message.ar', 'رصيد المحفظة غير كافٍ لإتمام الحجز.')
        ->assertJsonPath('message.en', 'Insufficient wallet balance to complete this booking.');
});

it('cannot book when not enough rooms of a type are available', function () {
    Booking::factory()->create([
        'hotel_id'       => $this->hotel->id,
        'user_id'        => $this->user->id,
        'check_in_date'  => now()->addDays(2),
        'check_out_date' => now()->addDays(5),
        'status'         => 'confirmed',
        'payment_method' => 'cash',
    ])->rooms()->attach($this->room->id);

    $response = $this->actingAs($this->user)
        ->postJson('/api/bookings', [
            'guest_full_name'  => 'Test Guest',
            'guest_phone'      => '0999999999',
            'hotel_id'         => $this->hotel->id,
            'rooms'            => [
                ['type' => 'single', 'quantity' => 1],
            ],
            'check_in_date'    => now()->addDays(3)->format('Y-m-d'),
            'check_out_date'   => now()->addDays(6)->format('Y-m-d'),
            'number_of_guests' => 2,
            'payment_method'   => 'cash',
        ]);

    $response->assertStatus(422)
        ->assertJsonPath('message.ar', 'لا يوجد عدد كافٍ من الغرف المتاحة.');
});

it('can apply a valid coupon', function () {
    $coupon = Coupon::factory()->create([
        'discount_type'  => 'percentage',
        'discount_value' => 10,
        'is_active'      => true,
        'expires_at'     => now()->addDays(10),
    ]);

    $response = $this->actingAs($this->user)
        ->postJson('/api/bookings', [
            'guest_full_name'  => 'Test Guest',
            'guest_phone'      => '0999999999',
            'hotel_id'         => $this->hotel->id,
            'rooms'            => [
                ['type' => 'single', 'quantity' => 1],
            ],
            'check_in_date'    => now()->addDays(2)->format('Y-m-d'),
            'check_out_date'   => now()->addDays(4)->format('Y-m-d'),
            'number_of_guests' => 2,
            'coupon_code'      => $coupon->code,
            'payment_method'   => 'cash',
        ]);

    $response->assertStatus(201);
    expect($response->json('data.discount_amount'))->toBeGreaterThan(0);
});

it('cannot apply an invalid coupon', function () {
    $coupon = Coupon::factory()->create([
        'is_active' => false,
    ]);

    $response = $this->actingAs($this->user)
        ->postJson('/api/bookings', [
            'guest_full_name'  => 'Test Guest',
            'guest_phone'      => '0999999999',
            'hotel_id'         => $this->hotel->id,
            'rooms'            => [
                ['type' => 'single', 'quantity' => 1],
            ],
            'check_in_date'    => now()->addDays(2)->format('Y-m-d'),
            'check_out_date'   => now()->addDays(4)->format('Y-m-d'),
            'number_of_guests' => 2,
            'coupon_code'      => $coupon->code,
            'payment_method'   => 'cash',
        ]);

    $response->assertStatus(422)
        ->assertJsonPath('message.en', 'This coupon is invalid or has expired.');
});

// ============================================================
// INDEX TESTS
// ============================================================

it('can list own bookings', function () {
    Booking::factory()->count(3)->create([
        'user_id'  => $this->user->id,
        'hotel_id' => $this->hotel->id,
    ])->each(fn($booking) => $booking->rooms()->attach($this->room->id));

    $response = $this->actingAs($this->user)
        ->getJson('/api/bookings');

    $response->assertOk()
        ->assertJsonCount(3, 'data');
});

it('cannot access bookings without token', function () {
    $response = $this->getJson('/api/bookings');
    $response->assertStatus(401);
});

// ============================================================
// UPDATE TESTS (manager/admin status changes)
// ============================================================

it('forbids a manager from updating a booking for a hotel they do not own', function () {
    $otherManager = User::factory()->create();
    $otherManager->assignRole('manager');

    $booking = Booking::factory()->create([
        'user_id'  => $this->user->id,
        'hotel_id' => $this->hotel->id,
        'status'   => 'pending',
    ]);
    $booking->rooms()->attach($this->room->id);

    $response = $this->actingAs($otherManager)
        ->patchJson("/api/bookings/{$booking->id}", ['status' => 'confirmed']);

    $response->assertStatus(403);

    $this->assertDatabaseHas('bookings', [
        'id'     => $booking->id,
        'status' => 'pending',
    ]);
});

it('allows the owning manager to update a booking status', function () {
    $manager = User::factory()->create();
    $manager->assignRole('manager');
    $this->hotel->update(['user_id' => $manager->id]);

    $booking = Booking::factory()->create([
        'user_id'  => $this->user->id,
        'hotel_id' => $this->hotel->id,
        'status'   => 'pending',
    ]);
    $booking->rooms()->attach($this->room->id);

    $response = $this->actingAs($manager)
        ->patchJson("/api/bookings/{$booking->id}", ['status' => 'confirmed']);

    $response->assertOk()
        ->assertJsonPath('data.status', 'confirmed');
});

it('allows an admin to update any booking status regardless of hotel ownership', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $booking = Booking::factory()->create([
        'user_id'  => $this->user->id,
        'hotel_id' => $this->hotel->id,
        'status'   => 'pending',
    ]);
    $booking->rooms()->attach($this->room->id);

    $response = $this->actingAs($admin)
        ->patchJson("/api/bookings/{$booking->id}", ['status' => 'completed']);

    $response->assertOk()
        ->assertJsonPath('data.status', 'completed');
});

it('rejects a plain user from hitting the update endpoint at all', function () {
    $booking = Booking::factory()->create([
        'user_id'  => $this->user->id,
        'hotel_id' => $this->hotel->id,
        'status'   => 'pending',
    ]);
    $booking->rooms()->attach($this->room->id);

    $response = $this->actingAs($this->user)
        ->patchJson("/api/bookings/{$booking->id}", ['status' => 'confirmed']);

    $response->assertStatus(403);
});

it('rejects setting status to cancelled through the update endpoint', function () {
    $manager = User::factory()->create();
    $manager->assignRole('manager');
    $this->hotel->update(['user_id' => $manager->id]);

    $booking = Booking::factory()->create([
        'user_id'  => $this->user->id,
        'hotel_id' => $this->hotel->id,
        'status'   => 'pending',
    ]);
    $booking->rooms()->attach($this->room->id);

    $response = $this->actingAs($manager)
        ->patchJson("/api/bookings/{$booking->id}", ['status' => 'cancelled']);

    $response->assertStatus(422);
});

// ============================================================
// CANCEL TESTS
// ============================================================

it('cancels a booking for free when more than 3 days remain before check-in', function () {
    $booking = Booking::factory()->create([
        'user_id'        => $this->user->id,
        'hotel_id'       => $this->hotel->id,
        'status'         => 'pending',
        'payment_method' => 'cash',
        'check_in_date'  => now()->addDays(10),
        'check_out_date' => now()->addDays(13),
        'final_price'    => 300,
    ]);
    $booking->rooms()->attach($this->room->id);

    $response = $this->actingAs($this->user)
        ->patchJson("/api/bookings/{$booking->id}/cancel");

    $response->assertOk();

    $this->assertDatabaseHas('bookings', [
        'id'     => $booking->id,
        'status' => 'cancelled',
    ]);
});

it('cannot cancel a booking that is already completed', function () {
    $booking = Booking::factory()->create([
        'user_id'        => $this->user->id,
        'hotel_id'       => $this->hotel->id,
        'status'         => 'completed',
        'payment_method' => 'cash',
        'check_in_date'  => now()->subDays(5),
        'check_out_date' => now()->subDays(2),
    ]);
    $booking->rooms()->attach($this->room->id);

    $response = $this->actingAs($this->user)
        ->patchJson("/api/bookings/{$booking->id}/cancel");

    $response->assertStatus(422);
});

it('returns the fee breakdown and waits for confirmation on a late cancellation, without touching the booking yet', function () {
    $booking = Booking::factory()->create([
        'user_id'        => $this->user->id,
        'hotel_id'       => $this->hotel->id,
        'status'         => 'pending',
        'payment_method' => 'cash',
        'check_in_date'  => now()->addDays(2), // within the 3-day window
        'check_out_date' => now()->addDays(5), // 3 nights
        'final_price'    => 300,
    ]);
    $booking->rooms()->attach($this->room->id);

    $response = $this->actingAs($this->user)
        ->patchJson("/api/bookings/{$booking->id}/cancel");

    $response->assertOk()
        ->assertJsonPath('requires_confirmation', true)
        ->assertJsonPath('fee', 100)
        ->assertJsonPath('message.ar', 'الإلغاء بعد أقل من 3 أيام من موعد الوصول يترتب عليه خصم غرامة قدرها 100 من محفظتك. هل تريد المتابعة؟');

    $this->assertDatabaseHas('bookings', [
        'id'     => $booking->id,
        'status' => 'pending',
    ]);
});

it('charges the late-cancellation fee from the wallet on a cash-paid booking', function () {
    $wallet = Wallet::factory()->create([
        'user_id' => $this->user->id,
        'balance' => 500,
    ]);

    $booking = Booking::factory()->create([
        'user_id'        => $this->user->id,
        'hotel_id'       => $this->hotel->id,
        'status'         => 'pending',
        'payment_method' => 'cash',
        'check_in_date'  => now()->addDays(2),
        'check_out_date' => now()->addDays(5),
        'final_price'    => 300,
    ]);
    $booking->rooms()->attach($this->room->id);

    $response = $this->actingAs($this->user)
        ->patchJson("/api/bookings/{$booking->id}/cancel", ['confirm' => true]);

    $response->assertOk()
        ->assertJsonPath('message.ar', 'تم إلغاء الحجز، وتم خصم 100 من محفظتك كغرامة إلغاء متأخر.');

    $this->assertDatabaseHas('bookings', [
        'id'     => $booking->id,
        'status' => 'cancelled',
    ]);

    expect((float) $wallet->fresh()->balance)->toBe(400.00); // 500 - 100 fee

    $this->assertDatabaseHas('wallet_transactions', [
        'wallet_id'        => $wallet->id,
        'amount'           => 100,
        'transaction_type' => 'debit',
    ]);
});

it('does not cancel a late cash booking if the wallet cannot cover the fee', function () {
    Wallet::factory()->create([
        'user_id' => $this->user->id,
        'balance' => 10,
    ]);

    $booking = Booking::factory()->create([
        'user_id'        => $this->user->id,
        'hotel_id'       => $this->hotel->id,
        'status'         => 'pending',
        'payment_method' => 'cash',
        'check_in_date'  => now()->addDays(2),
        'check_out_date' => now()->addDays(5),
        'final_price'    => 300,
    ]);
    $booking->rooms()->attach($this->room->id);

    $response = $this->actingAs($this->user)
        ->patchJson("/api/bookings/{$booking->id}/cancel", ['confirm' => true]);

    $response->assertStatus(422)
        ->assertJsonPath('message.ar', 'لا يوجد رصيد كافٍ لتغطية غرامة الإلغاء. لم يتم إلغاء الحجز، يرجى تعبئة محفظتك أولاً.');

    $this->assertDatabaseHas('bookings', [
        'id'     => $booking->id,
        'status' => 'pending',
    ]);
});

it('fails cleanly on a late cash cancellation when the user has no wallet at all', function () {
    $booking = Booking::factory()->create([
        'user_id'        => $this->user->id,
        'hotel_id'       => $this->hotel->id,
        'status'         => 'pending',
        'payment_method' => 'cash',
        'check_in_date'  => now()->addDays(2),
        'check_out_date' => now()->addDays(5),
        'final_price'    => 300,
    ]);
    $booking->rooms()->attach($this->room->id);

    $response = $this->actingAs($this->user)
        ->patchJson("/api/bookings/{$booking->id}/cancel", ['confirm' => true]);

    $response->assertStatus(422)
        ->assertJsonPath('message.ar', 'لا يوجد رصيد كافٍ لتغطية غرامة الإلغاء. لم يتم إلغاء الحجز، يرجى تعبئة محفظتك أولاً.');

    $this->assertDatabaseHas('bookings', [
        'id'     => $booking->id,
        'status' => 'pending',
    ]);
});

it('refunds the remaining balance to the wallet on a late wallet-paid cancellation', function () {
    $wallet = Wallet::factory()->create([
        'user_id' => $this->user->id,
        'balance' => 200,
    ]);

    $booking = Booking::factory()->create([
        'user_id'        => $this->user->id,
        'hotel_id'       => $this->hotel->id,
        'status'         => 'confirmed',
        'payment_method' => 'wallet',
        'check_in_date'  => now()->addDays(2),
        'check_out_date' => now()->addDays(5),
        'final_price'    => 300,
    ]);
    $booking->rooms()->attach($this->room->id);

    $response = $this->actingAs($this->user)
        ->patchJson("/api/bookings/{$booking->id}/cancel", ['confirm' => true]);

    $response->assertOk()
        ->assertJsonPath('message.ar', 'تم إلغاء الحجز. تم خصم غرامة 100 واسترجاع الباقي إلى محفظتك.');

    $this->assertDatabaseHas('bookings', [
        'id'     => $booking->id,
        'status' => 'cancelled',
    ]);

    // fee = 300/3 nights = 100, refund = 300 - 100 = 200
    expect((float) $wallet->fresh()->balance)->toBe(400.00);

    $this->assertDatabaseHas('wallet_transactions', [
        'wallet_id'        => $wallet->id,
        'amount'           => 200,
        'transaction_type' => 'credit',
    ]);
});

it('lets an admin cancel on behalf of a customer, moving money in the customer wallet not the admin wallet', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $customerWallet = Wallet::factory()->create([
        'user_id' => $this->user->id,
        'balance' => 500,
    ]);

    $booking = Booking::factory()->create([
        'user_id'        => $this->user->id,
        'hotel_id'       => $this->hotel->id,
        'status'         => 'pending',
        'payment_method' => 'cash',
        'check_in_date'  => now()->addDays(2),
        'check_out_date' => now()->addDays(5),
        'final_price'    => 300,
    ]);
    $booking->rooms()->attach($this->room->id);

    $response = $this->actingAs($admin)
        ->patchJson("/api/bookings/{$booking->id}/cancel", ['confirm' => true]);

    $response->assertOk();

    expect((float) $customerWallet->fresh()->balance)->toBe(400.00);
});

it('restores the coupon usage count when a coupon-booking is cancelled', function () {
    $coupon = Coupon::factory()->create([
        'used_count' => 1,
    ]);

    $booking = Booking::factory()->create([
        'user_id'        => $this->user->id,
        'hotel_id'       => $this->hotel->id,
        'status'         => 'pending',
        'payment_method' => 'cash',
        'coupon_id'      => $coupon->id,
        'check_in_date'  => now()->addDays(10),
        'check_out_date' => now()->addDays(13),
        'final_price'    => 300,
    ]);
    $booking->rooms()->attach($this->room->id);

    $this->actingAs($this->user)
        ->patchJson("/api/bookings/{$booking->id}/cancel")
        ->assertOk();

    $this->assertDatabaseHas('coupons', [
        'id'         => $coupon->id,
        'used_count' => 0,
    ]);
});

// ============================================================
// CANCEL - AUTHORIZATION
// ============================================================

it("forbids another plain user from cancelling someone else's booking", function () {
    $otherUser = User::factory()->create();
    $otherUser->assignRole('user');

    $booking = Booking::factory()->create([
        'user_id'        => $this->user->id,
        'hotel_id'       => $this->hotel->id,
        'status'         => 'pending',
        'payment_method' => 'cash',
        'check_in_date'  => now()->addDays(10),
        'check_out_date' => now()->addDays(13),
    ]);
    $booking->rooms()->attach($this->room->id);

    $response = $this->actingAs($otherUser)
        ->patchJson("/api/bookings/{$booking->id}/cancel");

    $response->assertStatus(403);

    $this->assertDatabaseHas('bookings', [
        'id'     => $booking->id,
        'status' => 'pending',
    ]);
});

it('forbids the owning hotel manager from cancelling a booking directly (only the customer or an admin can)', function () {
    $manager = User::factory()->create();
    $manager->assignRole('manager');
    $this->hotel->update(['user_id' => $manager->id]);

    $booking = Booking::factory()->create([
        'user_id'        => $this->user->id,
        'hotel_id'       => $this->hotel->id,
        'status'         => 'pending',
        'payment_method' => 'cash',
        'check_in_date'  => now()->addDays(10),
        'check_out_date' => now()->addDays(13),
    ]);
    $booking->rooms()->attach($this->room->id);

    $response = $this->actingAs($manager)
        ->patchJson("/api/bookings/{$booking->id}/cancel");

    $response->assertStatus(403);
});

// ============================================================
// CANCEL - STATUS GUARD
// ============================================================

it('cannot cancel a booking that is already cancelled', function () {
    $booking = Booking::factory()->create([
        'user_id'        => $this->user->id,
        'hotel_id'       => $this->hotel->id,
        'status'         => 'cancelled',
        'payment_method' => 'cash',
        'check_in_date'  => now()->addDays(10),
        'check_out_date' => now()->addDays(13),
    ]);
    $booking->rooms()->attach($this->room->id);

    $response = $this->actingAs($this->user)
        ->patchJson("/api/bookings/{$booking->id}/cancel");

    $response->assertStatus(422);
});

// ============================================================
// CANCEL - 3-DAY BOUNDARY
// ============================================================

it('treats exactly 3 days before check-in as a late cancellation', function () {
    $booking = Booking::factory()->create([
        'user_id'        => $this->user->id,
        'hotel_id'       => $this->hotel->id,
        'status'         => 'pending',
        'payment_method' => 'cash',
        'check_in_date'  => now()->addDays(3),
        'check_out_date' => now()->addDays(6),
        'final_price'    => 300,
    ]);
    $booking->rooms()->attach($this->room->id);

    $response = $this->actingAs($this->user)
        ->patchJson("/api/bookings/{$booking->id}/cancel");

    $response->assertOk()
        ->assertJsonPath('requires_confirmation', true);
});

it('treats exactly 4 days before check-in as a free cancellation', function () {
    $booking = Booking::factory()->create([
        'user_id'        => $this->user->id,
        'hotel_id'       => $this->hotel->id,
        'status'         => 'pending',
        'payment_method' => 'cash',
        'check_in_date'  => now()->addDays(4),
        'check_out_date' => now()->addDays(7),
        'final_price'    => 300,
    ]);
    $booking->rooms()->attach($this->room->id);

    $response = $this->actingAs($this->user)
        ->patchJson("/api/bookings/{$booking->id}/cancel");

    $response->assertOk();
});

// ============================================================
// CANCEL - COUPON RESTORE ON LATE (CONFIRMED) CANCELLATION
// ============================================================

it('restores the coupon usage count on a confirmed late cancellation too, not just the free one', function () {
    $coupon = Coupon::factory()->create([
        'used_count' => 1,
    ]);

    Wallet::factory()->create([
        'user_id' => $this->user->id,
        'balance' => 500,
    ]);

    $booking = Booking::factory()->create([
        'user_id'        => $this->user->id,
        'hotel_id'       => $this->hotel->id,
        'status'         => 'pending',
        'payment_method' => 'cash',
        'coupon_id'      => $coupon->id,
        'check_in_date'  => now()->addDays(2),
        'check_out_date' => now()->addDays(5),
        'final_price'    => 300,
    ]);
    $booking->rooms()->attach($this->room->id);

    $this->actingAs($this->user)
        ->patchJson("/api/bookings/{$booking->id}/cancel", ['confirm' => true])
        ->assertOk();

    $this->assertDatabaseHas('coupons', [
        'id'         => $coupon->id,
        'used_count' => 0,
    ]);
});

// ============================================================
// CANCEL - FEE ROUNDING
// ============================================================

it('rounds the late-cancellation fee to 2 decimals when it does not divide evenly', function () {
    Wallet::factory()->create([
        'user_id' => $this->user->id,
        'balance' => 500,
    ]);

    $booking = Booking::factory()->create([
        'user_id'        => $this->user->id,
        'hotel_id'       => $this->hotel->id,
        'status'         => 'pending',
        'payment_method' => 'cash',
        'check_in_date'  => now()->addDays(2),
        'check_out_date' => now()->addDays(5), // 3 nights
        'final_price'    => 100, // 100/3 = 33.33...
    ]);
    $booking->rooms()->attach($this->room->id);

    $response = $this->actingAs($this->user)
        ->patchJson("/api/bookings/{$booking->id}/cancel");

    $response->assertOk()
        ->assertJsonPath('fee', 33.33);
});
it('refunds the full amount to the wallet on a free (non-late) cancellation', function () {
    $wallet = Wallet::factory()->create([
        'user_id' => $this->user->id,
        'balance' => 100,
    ]);

    $booking = Booking::factory()->create([
        'user_id'        => $this->user->id,
        'hotel_id'       => $this->hotel->id,
        'status'         => 'confirmed',
        'payment_method' => 'wallet',
        'check_in_date'  => now()->addDays(10),
        'check_out_date' => now()->addDays(13),
        'final_price'    => 300,
    ]);
    $booking->rooms()->attach($this->room->id);

    $response = $this->actingAs($this->user)
        ->patchJson("/api/bookings/{$booking->id}/cancel");

    $response->assertOk();

    expect((float) $wallet->fresh()->balance)->toBe(400.00);

    $this->assertDatabaseHas('wallet_transactions', [
        'wallet_id'        => $wallet->id,
        'amount'           => 300,
        'transaction_type' => 'credit',
    ]);
});
