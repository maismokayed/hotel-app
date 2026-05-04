<?php

use App\Models\Hotel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Database\Seeders\RoleSeeder;
uses(RefreshDatabase::class);



beforeEach(function () {
    $this->seed(RoleSeeder::class);
});
// ============================================================
// INDEX
// ============================================================

it('returns only active hotels', function () {
    // بنعمل فندق نشط وفندق غير نشط
    Hotel::factory()->create(['is_active' => true]);
    Hotel::factory()->create(['is_active' => false]);

    // بنطلب قائمة الفنادق
    $response = $this->getJson('/api/hotels');

    // بنتحقق إن رجع فندق واحد بس (النشط)
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
    // بنعمل user وبنعطيه role admin
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    // بنسجل دخول كـ admin ونبعت بيانات الفندق
    $this->actingAs($admin)
         ->postJson('/api/hotels', [
             'name'        => 'Test Hotel',
             'city'        => 'Damascus',
             'address'     => 'Main Street',
             'star_rating' => 4,
         ])
         ->assertCreated(); // يعني 201
});

it('manager can create a hotel', function () {
    $manager = User::factory()->create();
    $manager->assignRole('manager');

    $this->actingAs($manager)
         ->postJson('/api/hotels', [
             'name'        => 'Test Hotel',
             'city'        => 'Aleppo',
             'address'     => 'Main Street',
             'star_rating' => 3,
         ])
         ->assertCreated();
});

it('guest cannot create a hotel', function () {
    // بدون تسجيل دخول
    $this->postJson('/api/hotels', [
             'name'    => 'Test Hotel',
             'city'    => 'Homs',
             'address' => 'Main Street',
         ])
         ->assertUnauthorized(); // يعني 401
});

it('rejects duplicate hotel in same city', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    // بنضيف الفندق أول مرة
    Hotel::factory()->create([
        'name' => 'Grand Hotel',
        'city' => 'Damascus',
    ]);

    // بنحاول نضيفه مرة ثانية
    $this->actingAs($admin)
         ->postJson('/api/hotels', [
             'name'    => 'Grand Hotel',
             'city'    => 'Damascus',
             'address' => 'Some Street',
         ])
         ->assertStatus(409); // conflict
});

it('rejects missing required fields', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    // بنبعت بدون name وaddress
    $this->actingAs($admin)
         ->postJson('/api/hotels', [])
         ->assertUnprocessable(); // يعني 422
});

// ============================================================
// UPDATE
// ============================================================

it('owner can update their hotel', function () {
    $manager = User::factory()->create();
    $manager->assignRole('manager');

    $hotel = Hotel::factory()->create(['user_id' => $manager->id]);

    $this->actingAs($manager)
         ->putJson("/api/hotels/{$hotel->id}", [
             'name' => 'Updated Name',
         ])
         ->assertOk();
});

it('admin can update any hotel', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    // الفندق لشخص ثاني
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

    // الفندق لشخص ثاني مش المانجر
    $hotel = Hotel::factory()->create();

    $this->actingAs($manager)
         ->putJson("/api/hotels/{$hotel->id}", [
             'name' => 'Hacked Name',
         ])
         ->assertForbidden(); // يعني 403
});

// ============================================================
// DESTROY
// ============================================================

it('owner can delete their hotel', function () {
    $manager = User::factory()->create();
    $manager->assignRole('manager');

    $hotel = Hotel::factory()->create(['user_id' => $manager->id]);

    $this->actingAs($manager)
         ->deleteJson("/api/hotels/{$hotel->id}")
         ->assertOk();

    // بنتحقق إن الفندق اتمسح فعلاً (soft delete)
    $this->assertSoftDeleted('hotels', ['id' => $hotel->id]);
});

it('admin can delete any hotel', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $hotel = Hotel::factory()->create();

    $this->actingAs($admin)
         ->deleteJson("/api/hotels/{$hotel->id}")
         ->assertOk();

    $this->assertSoftDeleted('hotels', ['id' => $hotel->id]);
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

    // بنتحقق إن الـ user_id تغير فعلاً
    $this->assertDatabaseHas('hotels', [
        'id'      => $hotel->id,
        'user_id' => $manager->id,
    ]);
});

it('cannot transfer hotel to a regular user', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    // user عادي بدون role
    $regularUser = User::factory()->create();

    $hotel = Hotel::factory()->create();

    $this->actingAs($admin)
         ->patchJson("/api/hotels/{$hotel->id}/transfer", [
             'user_id' => $regularUser->id,
         ])
         ->assertUnprocessable(); // 422
});