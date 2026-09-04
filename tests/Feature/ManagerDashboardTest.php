<?php

use App\Models\Booking;
use App\Models\Hotel;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    $this->seed(RolePermissionSeeder::class);

    $this->manager = User::factory()->create();
    $this->manager->assignRole('manager');

    $this->otherManager = User::factory()->create();
    $this->otherManager->assignRole('manager');

    $this->user = User::factory()->create();
    $this->user->assignRole('user');
});

// ============================================================
// AUTHORIZATION
// ============================================================

it('denies access to guests', function () {
    $this->getJson('/api/manager/dashboard')->assertUnauthorized();
});

it('denies access to regular users', function () {
    $this->actingAs($this->user)
        ->getJson('/api/manager/dashboard')
        ->assertForbidden();
});

it('allows a manager to view their dashboard', function () {
    $this->actingAs($this->manager)
        ->getJson('/api/manager/dashboard')
        ->assertOk()
        ->assertJsonStructure([
            'data' => [
                'hotels',
                'bookings',
                'revenue',
                'charts' => [
                    'monthly_booking_growth',
                ],
            ],
        ]);
});

// ============================================================
// SCOPING - a manager only ever sees their own hotels/bookings
// ============================================================

it('only counts hotels belonging to the logged in manager', function () {
    Hotel::factory()->count(2)->create(['user_id' => $this->manager->id]);
    Hotel::factory()->count(5)->create(['user_id' => $this->otherManager->id]);

    $response = $this->actingAs($this->manager)->getJson('/api/manager/dashboard');

    $response->assertJsonPath('data.hotels.total', 2);
});

it('only counts bookings made in the manager\'s own hotels', function () {
    $myHotel    = Hotel::factory()->create(['user_id' => $this->manager->id]);
    $otherHotel = Hotel::factory()->create(['user_id' => $this->otherManager->id]);

    Booking::factory()->create(['hotel_id' => $myHotel->id, 'status' => 'confirmed']);
    Booking::factory()->create(['hotel_id' => $myHotel->id, 'status' => 'cancelled']);
    Booking::factory()->create(['hotel_id' => $otherHotel->id, 'status' => 'confirmed']);

    $response = $this->actingAs($this->manager)->getJson('/api/manager/dashboard');

    $response->assertJsonPath('data.bookings.total', 2)
        ->assertJsonPath('data.bookings.by_status.confirmed', 1)
        ->assertJsonPath('data.bookings.by_status.cancelled', 1);
});

it('only sums revenue from confirmed/completed bookings in the manager\'s own hotels', function () {
    $myHotel    = Hotel::factory()->create(['user_id' => $this->manager->id]);
    $otherHotel = Hotel::factory()->create(['user_id' => $this->otherManager->id]);

    Booking::factory()->create([
        'hotel_id'    => $myHotel->id,
        'status'      => 'confirmed',
        'final_price' => 100,
    ]);
    Booking::factory()->create([
        'hotel_id'    => $myHotel->id,
        'status'      => 'pending',
        'final_price' => 999,
    ]);
    Booking::factory()->create([
        'hotel_id'    => $otherHotel->id,
        'status'      => 'confirmed',
        'final_price' => 500,
    ]);

    $response = $this->actingAs($this->manager)->getJson('/api/manager/dashboard');

    $response->assertJsonPath('data.revenue.total', 100);
});

it('returns 6 months of booking growth for the manager\'s hotels only', function () {
    $myHotel    = Hotel::factory()->create(['user_id' => $this->manager->id]);
    $otherHotel = Hotel::factory()->create(['user_id' => $this->otherManager->id]);

    Booking::factory()->create(['hotel_id' => $myHotel->id, 'created_at' => now()]);
    Booking::factory()->create(['hotel_id' => $myHotel->id, 'created_at' => now()]);
    Booking::factory()->create(['hotel_id' => $otherHotel->id, 'created_at' => now()]);

    $response = $this->actingAs($this->manager)->getJson('/api/manager/dashboard');
    $growth   = $response->json('data.charts.monthly_booking_growth');

    expect($growth)->toHaveCount(6);
    expect(collect($growth)->last()['count'])->toBe(2);
});
