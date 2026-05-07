<?php
use App\Models\User;
use App\Models\Room;
use App\Models\Hotel;
use App\Models\Booking;
use App\Models\Coupon;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;


uses(RefreshDatabase::class);

beforeEach(function () {
    app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    $this->seed(RoleSeeder::class);

    // يوزر عادي
    $this->user = User::factory()->create();
    $this->user->assignRole('user');

    // فندق وغرفة
    $this->hotel = Hotel::factory()->create();
    $this->room = Room::factory()->create([
        'hotel_id'       => $this->hotel->id,
        'price_per_night' => 100,
        'status'         => 'available',
    ]);
});

// ============================================================
// STORE TESTS
// ============================================================

it('can create a booking successfully', function () {
    $response = $this->actingAs($this->user)
        ->postJson('/api/bookings', [
            'room_id'          => $this->room->id,
            'check_in_date'    => now()->addDays(2)->format('Y-m-d'),
            'check_out_date'   => now()->addDays(5)->format('Y-m-d'),
            'number_of_guests' => 2,
            'payment_method'   => 'cash',
        ]);

    $response->assertStatus(201)
         ->assertJsonStructure([
             'data' => [
                 'id', 'room', 'user', 'check_in_date',
                 'check_out_date', 'status', 'total_price', 'final_price'
             ]
         ]);

    $this->assertDatabaseHas('bookings', [
        'user_id' => $this->user->id,
        'room_id' => $this->room->id,
        'status'  => 'pending',
    ]);
});

it('cannot book an unavailable room', function () {
    Booking::factory()->create([
        'room_id'        => $this->room->id,
        'user_id'        => $this->user->id,
        'check_in_date'  => now()->addDays(2),
        'check_out_date' => now()->addDays(5),
        'status'         => 'confirmed',
        'payment_method'   => 'cash',
    ]);

    $response = $this->actingAs($this->user)
        ->postJson('/api/bookings', [
            'room_id'          => $this->room->id,
            'check_in_date'    => now()->addDays(3)->format('Y-m-d'),
            'check_out_date'   => now()->addDays(6)->format('Y-m-d'),
            'number_of_guests' => 2,
                    'payment_method'   => 'cash',

        ]);

    $response->assertStatus(422)
             ->assertJson(['message' => 'الغرفة غير متاحة في هذه الفترة.']);
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
            'room_id'          => $this->room->id,
            'check_in_date'    => now()->addDays(2)->format('Y-m-d'),
            'check_out_date'   => now()->addDays(4)->format('Y-m-d'),
            'number_of_guests' => 2,
            'coupon_id'        => $coupon->id,
            'payment_method'   => 'cash',
        ]);

    $response->assertStatus(201);
expect($response->json('data.discount_amount'))->toBeGreaterThan(0);
});

it('cannot apply an invalid coupon', function () {
    $coupon = Coupon::factory()->create([
        'is_active'  => false,
    ]);

    $response = $this->actingAs($this->user)
        ->postJson('/api/bookings', [
            'room_id'          => $this->room->id,
            'check_in_date'    => now()->addDays(2)->format('Y-m-d'),
            'check_out_date'   => now()->addDays(4)->format('Y-m-d'),
            'number_of_guests' => 2,
            'coupon_id'        => $coupon->id,
            'payment_method'   => 'cash',
        ]);

    $response->assertStatus(422)
             ->assertJson(['message' => 'الكوبون غير صالح أو منتهي الصلاحية.']);
});

// ============================================================
// INDEX TESTS
// ============================================================

it('can list own bookings', function () {
    Booking::factory()->count(3)->create([
        'user_id' => $this->user->id,
        'room_id' => $this->room->id,
    ]);

    $response = $this->actingAs($this->user)
        ->getJson('/api/bookings');

   $response->assertOk()
         ->assertJsonCount(3, 'data');
});

// ============================================================
// CANCEL TESTS
// ============================================================

it('can cancel own pending booking', function () {
    $booking = Booking::factory()->create([
        'user_id' => $this->user->id,
        'room_id' => $this->room->id,
        'status'  => 'pending',
    ]);

    $response = $this->actingAs($this->user)
        ->patchJson("/api/bookings/{$booking->id}/cancel");

    $response->assertOk()
             ->assertJson(['message' => 'تم إلغاء الحجز بنجاح.']);

    $this->assertDatabaseHas('bookings', [
        'id'     => $booking->id,
        'status' => 'cancelled',
    ]);
});

it('cannot cancel a confirmed booking', function () {
    $booking = Booking::factory()->create([
        'user_id' => $this->user->id,
        'room_id' => $this->room->id,
        'status'  => 'confirmed',
    ]);

    $response = $this->actingAs($this->user)
        ->patchJson("/api/bookings/{$booking->id}/cancel");

    $response->assertStatus(422);
});

it('cannot access bookings without token', function () {
    $response = $this->getJson('/api/bookings');
    $response->assertStatus(401);
});
