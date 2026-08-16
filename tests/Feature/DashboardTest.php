<?php

use App\Models\Booking;
use App\Models\City;
use App\Models\Hotel;
use App\Models\Room;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    $this->seed(RoleSeeder::class);

    $this->admin = User::factory()->create();
    $this->admin->assignRole('admin');

    $this->user = User::factory()->create();
    $this->user->assignRole('user');
});

// ============================================================
// AUTHORIZATION
// ============================================================

it('denies access to guests', function () {
    $this->getJson('/api/dashboard')->assertUnauthorized();
});

it('denies access to non-admin users', function () {
    $this->actingAs($this->user)
        ->getJson('/api/dashboard')
        ->assertForbidden();
});

it('allows admin to view the dashboard', function () {
    $this->actingAs($this->admin)
        ->getJson('/api/dashboard')
        ->assertOk()
        ->assertJsonStructure([
            'bookings',
            'revenue',
            'hotels',
            'rooms',
            'wallet',
            'users',
            'charts' => [
                'monthly_booking_growth',
                'hotels_by_city',
                'top_hotels',
                'user_distribution',
            ],
        ]);
});

// ============================================================
// EXISTING STATS (regression - make sure old behavior didn't break)
// ============================================================

it('counts bookings by status correctly', function () {
    Booking::factory()->create(['status' => 'confirmed']);
    Booking::factory()->create(['status' => 'confirmed']);
    Booking::factory()->create(['status' => 'cancelled']);

    $response = $this->actingAs($this->admin)->getJson('/api/dashboard');

    $response->assertJsonPath('bookings.total', 3)
        ->assertJsonPath('bookings.by_status.confirmed', 2)
        ->assertJsonPath('bookings.by_status.cancelled', 1)
        ->assertJsonPath('bookings.by_status.pending', 0);
});

// ============================================================
// ✅ جديد - monthly_booking_growth
// ============================================================

it('returns 6 months of booking growth with zero-filled gaps', function () {
    Booking::factory()->create(['created_at' => now()]);
    Booking::factory()->create(['created_at' => now()]);
    Booking::factory()->create(['created_at' => now()->subMonths(2)]);

    $response = $this->actingAs($this->admin)->getJson('/api/dashboard');

    $growth = $response->json('charts.monthly_booking_growth');

    expect($growth)->toHaveCount(6);

    // آخر عنصر لازم يكون الشهر الحالي وفيه الحجزين
    expect(collect($growth)->last()['count'])->toBe(2);

    // مجموع كل الأشهر لازم يساوي 3 (كل الحجوزات تنعد مرة وحدة)
    expect(collect($growth)->sum('count'))->toBe(3);
});

// ============================================================
// ✅ جديد - hotels_by_city
// ============================================================

it('groups hotels by city correctly', function () {
    $damascus = City::factory()->create(['name_ar' => 'دمشق']);
    $aleppo   = City::factory()->create(['name_ar' => 'حلب']);

    Hotel::factory()->count(3)->create(['city_id' => $damascus->id]);
    Hotel::factory()->count(1)->create(['city_id' => $aleppo->id]);

    $response = $this->actingAs($this->admin)->getJson('/api/dashboard');

    $byCity = collect($response->json('charts.hotels_by_city'))->keyBy('city');

    expect($byCity['دمشق']['count'])->toBe(3);
    expect($byCity['حلب']['count'])->toBe(1);
});

it('excludes hotels without a city from the distribution', function () {
    Hotel::factory()->create(['city_id' => null]);

    $response = $this->actingAs($this->admin)->getJson('/api/dashboard');

    expect(collect($response->json('charts.hotels_by_city'))->sum('count'))->toBe(0);
});

// ============================================================
// ✅ جديد - top_hotels
// ============================================================

it('ranks hotels by booking count, limited to 5', function () {
    $topHotel = Hotel::factory()->create();
    Booking::factory()->count(5)->create(['hotel_id' => $topHotel->id]);

    $otherHotels = Hotel::factory()->count(6)->create();
    foreach ($otherHotels as $hotel) {
        Booking::factory()->create(['hotel_id' => $hotel->id]);
    }

    $response = $this->actingAs($this->admin)->getJson('/api/dashboard');
    $topHotels = $response->json('charts.top_hotels');

    expect($topHotels)->toHaveCount(5);
    expect($topHotels[0]['hotel_id'])->toBe($topHotel->id);
    expect($topHotels[0]['bookings'])->toBe(5);
});

it('excludes hotels with no bookings from top_hotels', function () {
    Hotel::factory()->create(); // بدون حجوزات

    $response = $this->actingAs($this->admin)->getJson('/api/dashboard');

    expect($response->json('charts.top_hotels'))->toHaveCount(0);
});

// ============================================================
// ✅ جديد - user_distribution
// ============================================================

it('distributes users by role', function () {
    User::factory()->count(2)->create()->each(fn($u) => $u->assignRole('user'));

    $manager = User::factory()->create();
    $manager->assignRole('manager');

    $response = $this->actingAs($this->admin)->getJson('/api/dashboard');
    $distribution = collect($response->json('charts.user_distribution'))->keyBy('role');

    // 2 مستخدمين جداد + $this->user من الـ beforeEach = 3
    expect($distribution['user']['count'])->toBe(3);
    expect($distribution['manager']['count'])->toBe(1);
    expect($distribution['admin']['count'])->toBe(1); // $this->admin
});
