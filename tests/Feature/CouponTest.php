<?php

use App\Models\User;
use App\Models\Coupon;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    $this->seed(RoleSeeder::class);

    // admin
    $this->admin = User::factory()->create();
    $this->admin->assignRole('admin');

    // user عادي
    $this->user = User::factory()->create();
    $this->user->assignRole('user');
});

// ============================================================
// INDEX
// ============================================================

it('admin can list all coupons', function () {
    Coupon::factory()->count(3)->create();

    $response = $this->actingAs($this->admin)
        ->getJson('/api/coupons');

    $response->assertOk()
        ->assertJsonCount(3, 'data');
});

it('user cannot list coupons', function () {
    $response = $this->actingAs($this->user)
        ->getJson('/api/coupons');

    $response->assertStatus(403);
});

// ============================================================
// STORE
// ============================================================

it('admin can create a coupon', function () {
    $response = $this->actingAs($this->admin)
        ->postJson('/api/coupons', [
            'code'           => 'SAVE10',
            'discount_type'  => 'percentage',
            'discount_value' => 10,
            'is_active'      => true,
        ]);

    $response->assertStatus(201)
        ->assertJsonStructure(['data' => ['id', 'code', 'discount_type', 'discount_value']]);

    $this->assertDatabaseHas('coupons', ['code' => 'SAVE10']);
});

it('user cannot create a coupon', function () {
    $response = $this->actingAs($this->user)
        ->postJson('/api/coupons', [
            'code'           => 'SAVE10',
            'discount_type'  => 'percentage',
            'discount_value' => 10,
        ]);

    $response->assertStatus(403);
});

// ============================================================
// UPDATE
// ============================================================

it('admin can update a coupon', function () {
    $coupon = Coupon::factory()->create();

    $response = $this->actingAs($this->admin)
        ->putJson("/api/coupons/{$coupon->id}", [
            'is_active' => false,
        ]);

    $response->assertOk();
    $this->assertDatabaseHas('coupons', [
        'id'        => $coupon->id,
        'is_active' => false,
    ]);
});

// ============================================================
// DESTROY
// ============================================================

it('admin can delete a coupon', function () {
    $coupon = Coupon::factory()->create();

    $response = $this->actingAs($this->admin)
        ->deleteJson("/api/coupons/{$coupon->id}");

    $response->assertOk()
        ->assertJson(['message' => 'تم حذف الكوبون بنجاح.']);

    $this->assertDatabaseMissing('coupons', ['id' => $coupon->id]);
});

it('user cannot delete a coupon', function () {
    $coupon = Coupon::factory()->create();

    $response = $this->actingAs($this->user)
        ->deleteJson("/api/coupons/{$coupon->id}");

    $response->assertStatus(403);
});

// ============================================================
// GUEST
// ============================================================

it('cannot access coupons without token', function () {
    $response = $this->getJson('/api/coupons');
    $response->assertStatus(401);
});
// ============================================================
// CHECK (validate coupon)
// ============================================================

it('user can check a valid coupon', function () {
    $coupon = Coupon::factory()->create([
        'code'           => 'VALID10',
        'discount_type'  => 'percentage',
        'discount_value' => 10,
        'is_active'      => true,
        'expires_at'     => now()->addDays(5),
        'max_uses'       => 100,
        'used_count'     => 5,
    ]);

    $response = $this->actingAs($this->user)
        ->getJson('/api/coupons/check?code=VALID10');

    $response->assertOk()
        ->assertJson([
            'valid' => true,
        ])
        ->assertJsonStructure([
            'valid',
            'message' => ['ar', 'en'],
            'data'    => ['code', 'discount_type', 'discount_value'],
        ]);
});

it('returns not found for a non-existent coupon code', function () {
    $response = $this->actingAs($this->user)
        ->getJson('/api/coupons/check?code=NOTEXIST');

    $response->assertStatus(404)
        ->assertJson(['valid' => false]);
});

it('returns invalid for an inactive coupon', function () {
    $coupon = Coupon::factory()->create([
        'code'      => 'INACTIVE10',
        'is_active' => false,
    ]);

    $response = $this->actingAs($this->user)
        ->getJson('/api/coupons/check?code=INACTIVE10');

    $response->assertOk()
        ->assertJson(['valid' => false]);
});

it('returns invalid for an expired coupon', function () {
    $coupon = Coupon::factory()->create([
        'code'       => 'EXPIRED10',
        'is_active'  => true,
        'expires_at' => now()->subDay(),
    ]);

    $response = $this->actingAs($this->user)
        ->getJson('/api/coupons/check?code=EXPIRED10');

    $response->assertOk()
        ->assertJson(['valid' => false]);
});

it('returns invalid when max uses is reached', function () {
    $coupon = Coupon::factory()->create([
        'code'       => 'MAXEDOUT',
        'is_active'  => true,
        'max_uses'   => 3,
        'used_count' => 3,
    ]);

    $response = $this->actingAs($this->user)
        ->getJson('/api/coupons/check?code=MAXEDOUT');

    $response->assertOk()
        ->assertJson(['valid' => false]);
});

it('requires code parameter to check a coupon', function () {
    $response = $this->actingAs($this->user)
        ->getJson('/api/coupons/check');

    $response->assertStatus(422);
});

it('admin can also check a coupon', function () {
    $coupon = Coupon::factory()->create([
        'code'      => 'ADMINCHECK',
        'is_active' => true,
    ]);

    $response = $this->actingAs($this->admin)
        ->getJson('/api/coupons/check?code=ADMINCHECK');

    $response->assertOk()
        ->assertJson(['valid' => true]);
});

it('guest cannot check a coupon without token', function () {
    $coupon = Coupon::factory()->create(['code' => 'GUESTCHECK']);

    $response = $this->getJson('/api/coupons/check?code=GUESTCHECK');

    $response->assertStatus(401);
});
