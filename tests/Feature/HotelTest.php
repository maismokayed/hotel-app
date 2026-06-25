<?php

use App\Models\City;
use App\Models\Hotel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Database\Seeders\RoleSeeder;
use Database\Seeders\CitySeeder;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed([
        RoleSeeder::class,
        CitySeeder::class,
    ]);
});

// ============================================================
// INDEX
// ============================================================

it('returns only active hotels', function () {
    Hotel::factory()->create(['is_active' => true]);
    Hotel::factory()->create(['is_active' => false]);

    $response = $this->getJson('/api/hotels');

    $response->assertOk()
        ->assertJsonCount(1, 'data');
});

// ============================================================
// SHOW
// ============================================================

it('returns hotel if active', function () {
    $hotel = Hotel::factory()->create(['is_active' => true]);

    $this->getJson("/api/hotels/{$hotel->id}")
        ->assertOk();
});

it('returns 404 if hotel is not active', function () {
    $hotel = Hotel::factory()->create(['is_active' => false]);

    $this->getJson("/api/hotels/{$hotel->id}")
        ->assertNotFound();
});

// ============================================================
// STORE
// ============================================================

it('admin can create a hotel', function () {
    $city = City::first();

    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $this->actingAs($admin)
        ->postJson('/api/hotels', [
            'name'        => 'Test Hotel',
            'city_id'     => $city->id,
            'address'     => 'Main Street',
            'star_rating' => 4,
        ])
        ->assertCreated();
});

it('manager can create a hotel', function () {
    $city = City::first();

    $manager = User::factory()->create();
    $manager->assignRole('manager');

    $this->actingAs($manager)
        ->postJson('/api/hotels', [
            'name'        => 'Test Hotel',
            'city_id'     => $city->id,
            'address'     => 'Main Street',
            'star_rating' => 3,
        ])
        ->assertCreated();
});

it('guest cannot create a hotel', function () {
    $city = City::first();

    $this->postJson('/api/hotels', [
        'name'    => 'Test Hotel',
        'city_id' => $city->id,
        'address' => 'Main Street',
    ])
        ->assertUnauthorized();
});

it('rejects duplicate hotel in same city', function () {
    $city = City::first();

    $admin = User::factory()->create();
    $admin->assignRole('admin');

    Hotel::factory()->create([
        'name'    => 'Grand Hotel',
        'city_id' => $city->id,
    ]);

    $this->actingAs($admin)
        ->postJson('/api/hotels', [
            'name'    => 'Grand Hotel',
            'city_id' => $city->id,
            'address' => 'Some Street',
        ])
        ->assertStatus(409);
});

it('rejects missing required fields', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $this->actingAs($admin)
        ->postJson('/api/hotels', [])
        ->assertUnprocessable();
});

// ============================================================
// UPDATE
// ============================================================

it('owner can update their hotel', function () {
    $manager = User::factory()->create();
    $manager->assignRole('manager');

    $hotel = Hotel::factory()->create([
        'user_id' => $manager->id,
    ]);

    $this->actingAs($manager)
        ->putJson("/api/hotels/{$hotel->id}", [
            'name' => 'Updated Name',
        ])
        ->assertOk();
});

it('admin can update any hotel', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $hotel = Hotel::factory()->create();

    $this->actingAs($admin)
        ->putJson("/api/hotels/{$hotel->id}", [
            'name' => 'Updated By Admin',
        ])
        ->assertOk();
});

it('manager cannot update hotel they do not own', function () {
    $manager = User::factory()->create();
    $manager->assignRole('manager');

    $hotel = Hotel::factory()->create();

    $this->actingAs($manager)
        ->putJson("/api/hotels/{$hotel->id}", [
            'name' => 'Hacked Name',
        ])
        ->assertForbidden();
});

// ============================================================
// DESTROY
// ============================================================

it('owner can delete their hotel', function () {
    $manager = User::factory()->create();
    $manager->assignRole('manager');

    $hotel = Hotel::factory()->create([
        'user_id' => $manager->id,
    ]);

    $this->actingAs($manager)
        ->deleteJson("/api/hotels/{$hotel->id}")
        ->assertOk();

    $this->assertSoftDeleted('hotels', [
        'id' => $hotel->id,
    ]);
});

it('admin can delete any hotel', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $hotel = Hotel::factory()->create();

    $this->actingAs($admin)
        ->deleteJson("/api/hotels/{$hotel->id}")
        ->assertOk();

    $this->assertSoftDeleted('hotels', [
        'id' => $hotel->id,
    ]);
});

it('manager cannot delete hotel they do not own', function () {
    $manager = User::factory()->create();
    $manager->assignRole('manager');

    $hotel = Hotel::factory()->create();

    $this->actingAs($manager)
        ->deleteJson("/api/hotels/{$hotel->id}")
        ->assertForbidden();
});

// ============================================================
// TRANSFER
// ============================================================

it('admin can transfer hotel to a manager', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $manager = User::factory()->create();
    $manager->assignRole('manager');

    $hotel = Hotel::factory()->create();

    $this->actingAs($admin)
        ->patchJson("/api/hotels/{$hotel->id}/transfer", [
            'user_id' => $manager->id,
        ])
        ->assertOk();

    $this->assertDatabaseHas('hotels', [
        'id'      => $hotel->id,
        'user_id' => $manager->id,
    ]);
});

it('cannot transfer hotel to a regular user', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $regularUser = User::factory()->create();

    $hotel = Hotel::factory()->create();

    $this->actingAs($admin)
        ->patchJson("/api/hotels/{$hotel->id}/transfer", [
            'user_id' => $regularUser->id,
        ])
        ->assertUnprocessable();
});
