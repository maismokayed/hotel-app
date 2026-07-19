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

// ============================================================
// STORE TESTS
// ============================================================

it('can create a booking successfully', function () {
    $response = $this->actingAs($this->user)
        ->postJson('/api/bookings', [
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
        ]);

    $this->assertDatabaseHas('bookings', [
        'user_id'  => $this->user->id,
        'hotel_id' => $this->hotel->id,
        'status'   => 'pending',
    ]);

    $this->assertDatabaseHas('booking_room', [
        'room_id' => $this->room->id,
    ]);
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
        ->assertJson(['message' => 'Not enough available rooms.']);
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
        ->assertJson(['message' => 'الكوبون غير صالح أو منتهي الصلاحية.']);
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

// ============================================================
// CANCEL TESTS
// ============================================================

it('can cancel own pending booking', function () {
    $booking = Booking::factory()->create([
        'user_id'  => $this->user->id,
        'hotel_id' => $this->hotel->id,
        'status'   => 'pending',
    ]);
    $booking->rooms()->attach($this->room->id);

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
        'user_id'  => $this->user->id,
        'hotel_id' => $this->hotel->id,
        'status'   => 'confirmed',
    ]);
    $booking->rooms()->attach($this->room->id);

    $response = $this->actingAs($this->user)
        ->patchJson("/api/bookings/{$booking->id}/cancel");

    $response->assertStatus(422);
});

it('cannot access bookings without token', function () {
    $response = $this->getJson('/api/bookings');
    $response->assertStatus(401);
});
