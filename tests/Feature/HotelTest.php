<?php

use App\Models\City;
use App\Models\Hotel;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
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

it('returns all hotels regardless of active status', function () {
    Hotel::factory()->create(['is_active' => true]);
    Hotel::factory()->create(['is_active' => false]);

    $response = $this->getJson('/api/hotels');

    $response->assertOk()
        ->assertJsonCount(2, 'data');
});

it('filters hotels by name_en', function () {
    Hotel::factory()->create(['name_en' => 'Grand Palace Hotel']);
    Hotel::factory()->create(['name_en' => 'Sea View Resort']);

    $this->getJson('/api/hotels?name=Palace')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name.en', 'Grand Palace Hotel');
});

it('filters hotels by city name', function () {
    // بنستخدم أسماء مش موجودة بـ CitySeeder الحقيقي تفادياً لتضارب unique constraint
    $cityA = City::factory()->create(['name_en' => 'ZzTestCityAlpha']);
    $cityB = City::factory()->create(['name_en' => 'ZzTestCityBeta']);

    Hotel::factory()->create(['city_id' => $cityA->id]);
    Hotel::factory()->create(['city_id' => $cityB->id]);

    $this->getJson('/api/hotels?name=Alpha')
        ->assertOk()
        ->assertJsonCount(1, 'data');
});

it('filters hotels by city_id', function () {
    $city = City::factory()->create();
    Hotel::factory()->create(['city_id' => $city->id]);
    Hotel::factory()->create();

    $this->getJson("/api/hotels?city_id={$city->id}")
        ->assertOk()
        ->assertJsonCount(1, 'data');
});

it('filters hotels by star_rating', function () {
    Hotel::factory()->create(['star_rating' => 5]);
    Hotel::factory()->create(['star_rating' => 3]);

    $this->getJson('/api/hotels?star_rating=5')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.star_rating', 5);
});

it('sorts hotels by popularity when requested', function () {
    $popular = Hotel::factory()->create();
    $unpopular = Hotel::factory()->create();

    \App\Models\Booking::factory()->create(['hotel_id' => $popular->id]);

    $this->getJson('/api/hotels?sort=popular')
        ->assertOk()
        ->assertJsonPath('data.0.id', $popular->id);
});

it('paginates hotels with 10 per page', function () {
    Hotel::factory()->count(15)->create();

    $response = $this->getJson('/api/hotels');

    // ملاحظة: هالتست بيكشف باغ حالي بـ index() — $hotels->load(...) عم يرجع
    // Collection عادية مش الـ paginator، فبتخسر links/meta. لازم يتصلح بالكونترولر:
    // $hotels->getCollection()->load(...); return HotelResource::collection($hotels);
    $response->assertOk()
        ->assertJsonCount(10, 'data')
        ->assertJsonStructure(['data', 'links', 'meta']);
});

// ============================================================
// SHOW
// ============================================================

it('shows hotel even if not active', function () {
    $hotel = Hotel::factory()->create(['is_active' => false]);

    $this->getJson("/api/hotels/{$hotel->id}")
        ->assertOk()
        ->assertJsonPath('data.is_active', false);
});

it('returns 404 for a non-existent hotel', function () {
    $this->getJson('/api/hotels/999999')
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
            'name_ar'     => 'فندق تجريبي',
            'name_en'     => 'Test Hotel',
            'city_id'     => $city->id,
            'address_ar'  => 'الشارع الرئيسي',
            'address_en'  => 'Main Street',
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
            'name_ar'     => 'فندق تجريبي',
            'name_en'     => 'Test Hotel',
            'city_id'     => $city->id,
            'address_ar'  => 'الشارع الرئيسي',
            'address_en'  => 'Main Street',
            'star_rating' => 3,
        ])
        ->assertCreated();
});

it('guest cannot create a hotel', function () {
    $city = City::first();

    $this->postJson('/api/hotels', [
        'name_ar'    => 'فندق تجريبي',
        'name_en'    => 'Test Hotel',
        'city_id'    => $city->id,
        'address_ar' => 'الشارع الرئيسي',
        'address_en' => 'Main Street',
    ])
        ->assertUnauthorized();
});

it('regular user cannot create a hotel', function () {
    $city = City::first();
    $user = User::factory()->create();
    $user->assignRole('user');

    $this->actingAs($user)
        ->postJson('/api/hotels', [
            'name_ar'    => 'فندق تجريبي',
            'name_en'    => 'Test Hotel',
            'city_id'    => $city->id,
            'address_ar' => 'الشارع الرئيسي',
            'address_en' => 'Main Street',
        ])
        ->assertForbidden();
});

it('rejects duplicate hotel in same city', function () {
    $city = City::first();

    $admin = User::factory()->create();
    $admin->assignRole('admin');

    Hotel::factory()->create([
        'name_ar' => 'فندق جراند',
        'name_en' => 'Grand Hotel',
        'city_id' => $city->id,
    ]);

    $this->actingAs($admin)
        ->postJson('/api/hotels', [
            'name_ar'    => 'فندق جراند',
            'name_en'    => 'Grand Hotel',
            'city_id'    => $city->id,
            'address_ar' => 'شارع آخر',
            'address_en' => 'Some Street',
        ])
        ->assertStatus(409);
});

it('allows same hotel name in a different city', function () {
    $cityA = City::factory()->create();
    $cityB = City::factory()->create();

    $admin = User::factory()->create();
    $admin->assignRole('admin');

    Hotel::factory()->create([
        'name_ar' => 'فندق جراند',
        'name_en' => 'Grand Hotel',
        'city_id' => $cityA->id,
    ]);

    $this->actingAs($admin)
        ->postJson('/api/hotels', [
            'name_ar'    => 'فندق جراند',
            'name_en'    => 'Grand Hotel',
            'city_id'    => $cityB->id,
            'address_ar' => 'شارع آخر',
            'address_en' => 'Some Street',
        ])
        ->assertCreated();
});

it('rejects missing required fields', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $this->actingAs($admin)
        ->postJson('/api/hotels', [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['name_ar', 'name_en', 'city_id', 'address_ar', 'address_en']);
});

it('rejects invalid email format', function () {
    $city = City::first();
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $this->actingAs($admin)
        ->postJson('/api/hotels', [
            'name_ar'    => 'فندق تجريبي',
            'name_en'    => 'Test Hotel',
            'city_id'    => $city->id,
            'address_ar' => 'الشارع الرئيسي',
            'address_en' => 'Main Street',
            'email'      => 'not-an-email',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['email']);
});

it('rejects star_rating outside 1-5 range', function () {
    $city = City::first();
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $this->actingAs($admin)
        ->postJson('/api/hotels', [
            'name_ar'     => 'فندق تجريبي',
            'name_en'     => 'Test Hotel',
            'city_id'     => $city->id,
            'address_ar'  => 'الشارع الرئيسي',
            'address_en'  => 'Main Street',
            'star_rating' => 6,
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['star_rating']);
});

it('rejects a non-existent city_id', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $this->actingAs($admin)
        ->postJson('/api/hotels', [
            'name_ar'    => 'فندق تجريبي',
            'name_en'    => 'Test Hotel',
            'city_id'    => 999999,
            'address_ar' => 'الشارع الرئيسي',
            'address_en' => 'Main Street',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['city_id']);
});

// ============================================================
// UPDATE
// ============================================================

it('owner can update their hotel', function () {
    $hotel = Hotel::factory()->create();
    $hotel->user->assignRole('manager');

    $this->actingAs($hotel->user)
        ->putJson("/api/hotels/{$hotel->id}", [
            'name_ar' => 'اسم محدث',
            'name_en' => 'Updated Name',
        ])
        ->assertOk();
});

it('owner can update only a single field (partial update)', function () {
    $hotel = Hotel::factory()->create(['star_rating' => 3]);
    $hotel->user->assignRole('manager');

    $this->actingAs($hotel->user)
        ->putJson("/api/hotels/{$hotel->id}", [
            'star_rating' => 5,
        ])
        ->assertOk()
        ->assertJsonPath('hotel.star_rating', 5);

    expect($hotel->fresh()->name_en)->toBe($hotel->name_en);
});

it('admin can update any hotel', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $hotel = Hotel::factory()->create();

    $this->actingAs($admin)
        ->putJson("/api/hotels/{$hotel->id}", [
            'name_ar' => 'محدث من الأدمن',
            'name_en' => 'Updated By Admin',
        ])
        ->assertOk();
});

it('manager cannot update hotel they do not own', function () {
    $manager = User::factory()->create();
    $manager->assignRole('manager');

    $hotel = Hotel::factory()->create();

    $this->actingAs($manager)
        ->putJson("/api/hotels/{$hotel->id}", [
            'name_ar' => 'اسم مخترق',
            'name_en' => 'Hacked Name',
        ])
        ->assertForbidden();
});

it('guest cannot update a hotel', function () {
    $hotel = Hotel::factory()->create();

    $this->putJson("/api/hotels/{$hotel->id}", [
        'name_ar' => 'اسم مخترق',
    ])->assertUnauthorized();
});

it('rejects update that duplicates another hotel in same city', function () {
    $city = City::first();

    $hotel = Hotel::factory()->create(['city_id' => $city->id]);
    $hotel->user->assignRole('manager');

    Hotel::factory()->create([
        'name_en' => 'Existing Hotel',
        'city_id' => $city->id,
    ]);

    $this->actingAs($hotel->user)
        ->putJson("/api/hotels/{$hotel->id}", [
            'name_en' => 'Existing Hotel',
        ])
        ->assertStatus(409);
});

// ============================================================
// DESTROY
// ============================================================

it('owner can delete their hotel', function () {
    $hotel = Hotel::factory()->create();
    $hotel->user->assignRole('manager');

    $this->actingAs($hotel->user)
        ->deleteJson("/api/hotels/{$hotel->id}")
        ->assertOk();

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

it('guest cannot delete a hotel', function () {
    $hotel = Hotel::factory()->create();

    $this->deleteJson("/api/hotels/{$hotel->id}")
        ->assertUnauthorized();
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

it('rejects transferring to a non-existent user', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $hotel = Hotel::factory()->create();

    // TransferHotelRequest فيها exists:users,id، فبترفض بـ 422 قبل ما توصل لمنطق
    // الـ 404 الموجود جوا الكونترولر (يعني هاد الشرط الأخير عملياً dead code).
    $this->actingAs($admin)
        ->patchJson("/api/hotels/{$hotel->id}/transfer", [
            'user_id' => 999999,
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['user_id']);
});

it('manager cannot transfer a hotel (admin only)', function () {
    $manager = User::factory()->create();
    $manager->assignRole('manager');

    $anotherManager = User::factory()->create();
    $anotherManager->assignRole('manager');

    $hotel = Hotel::factory()->create();

    $this->actingAs($manager)
        ->patchJson("/api/hotels/{$hotel->id}/transfer", [
            'user_id' => $anotherManager->id,
        ])
        ->assertForbidden();
});

it('guest cannot transfer a hotel', function () {
    $manager = User::factory()->create();
    $manager->assignRole('manager');

    $hotel = Hotel::factory()->create();

    $this->patchJson("/api/hotels/{$hotel->id}/transfer", [
        'user_id' => $manager->id,
    ])->assertUnauthorized();
});

// ============================================================
// IMAGES (upload / list / delete)
// ============================================================

it('owner can upload a hotel image', function () {
    Storage::fake('public');

    $hotel = Hotel::factory()->create();
    $hotel->user->assignRole('manager');

    $this->actingAs($hotel->user)
        ->postJson("/api/hotels/{$hotel->id}/images", [
            'image' => UploadedFile::fake()->image('hotel.jpg'),
        ])
        ->assertOk()
        ->assertJsonStructure(['id', 'url']);

    expect($hotel->fresh()->getMedia('images'))->toHaveCount(1);
});

it('rejects a non-image file on upload', function () {
    Storage::fake('public');

    $hotel = Hotel::factory()->create();
    $hotel->user->assignRole('manager');

    $this->actingAs($hotel->user)
        ->postJson("/api/hotels/{$hotel->id}/images", [
            'image' => UploadedFile::fake()->create('document.pdf', 100),
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['image']);
});

it('non-owner cannot upload an image', function () {
    Storage::fake('public');

    $manager = User::factory()->create();
    $manager->assignRole('manager');

    $hotel = Hotel::factory()->create();

    $this->actingAs($manager)
        ->postJson("/api/hotels/{$hotel->id}/images", [
            'image' => UploadedFile::fake()->image('hotel.jpg'),
        ])
        ->assertForbidden();
});

it('guest cannot upload an image', function () {
    $hotel = Hotel::factory()->create();

    $this->postJson("/api/hotels/{$hotel->id}/images", [
        'image' => UploadedFile::fake()->image('hotel.jpg'),
    ])->assertUnauthorized();
});

it('anyone can list hotel images', function () {
    Storage::fake('public');

    $hotel = Hotel::factory()->create();
    $hotel->addMedia(UploadedFile::fake()->image('hotel.jpg'))
        ->toMediaCollection('images');

    $this->getJson("/api/hotels/{$hotel->id}/images")
        ->assertOk()
        ->assertJsonCount(1, 'images');
});

it('owner can delete a hotel image', function () {
    Storage::fake('public');

    $hotel = Hotel::factory()->create();
    $hotel->user->assignRole('manager');

    $media = $hotel->addMedia(UploadedFile::fake()->image('hotel.jpg'))
        ->toMediaCollection('images');

    $this->actingAs($hotel->user)
        ->deleteJson("/api/hotels/{$hotel->id}/images/{$media->id}")
        ->assertOk();

    expect($hotel->fresh()->getMedia('images'))->toHaveCount(0);
});

it('returns 404 when deleting a non-existent image', function () {
    $hotel = Hotel::factory()->create();
    $hotel->user->assignRole('manager');

    $this->actingAs($hotel->user)
        ->deleteJson("/api/hotels/{$hotel->id}/images/999999")
        ->assertNotFound();
});

it('non-owner cannot delete a hotel image', function () {
    Storage::fake('public');

    $manager = User::factory()->create();
    $manager->assignRole('manager');

    $hotel = Hotel::factory()->create();
    $media = $hotel->addMedia(UploadedFile::fake()->image('hotel.jpg'))
        ->toMediaCollection('images');

    $this->actingAs($manager)
        ->deleteJson("/api/hotels/{$hotel->id}/images/{$media->id}")
        ->assertForbidden();
});

// ============================================================
// SERVICES
// ============================================================

it('owner can sync services for their hotel', function () {
    $hotel = Hotel::factory()->create();
    $hotel->user->assignRole('manager');

    $services = Service::factory()->count(3)->create();

    $this->actingAs($hotel->user)
        ->postJson("/api/hotels/{$hotel->id}/services", [
            'service_ids' => $services->pluck('id')->toArray(),
        ])
        ->assertOk();

    expect($hotel->fresh()->services)->toHaveCount(3);
});

it('rejects sync with non-existent service ids', function () {
    $hotel = Hotel::factory()->create();
    $hotel->user->assignRole('manager');

    $this->actingAs($hotel->user)
        ->postJson("/api/hotels/{$hotel->id}/services", [
            'service_ids' => [999999],
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['service_ids.0']);
});

it('non-owner cannot sync services', function () {
    $manager = User::factory()->create();
    $manager->assignRole('manager');

    $hotel = Hotel::factory()->create();
    $service = Service::factory()->create();

    $this->actingAs($manager)
        ->postJson("/api/hotels/{$hotel->id}/services", [
            'service_ids' => [$service->id],
        ])
        ->assertForbidden();
});

it('guest cannot sync services', function () {
    $hotel = Hotel::factory()->create();
    $service = Service::factory()->create();

    $this->postJson("/api/hotels/{$hotel->id}/services", [
        'service_ids' => [$service->id],
    ])->assertUnauthorized();
});

// ============================================================
// STATUS
// ============================================================

it('owner can update hotel status', function () {
    $hotel = Hotel::factory()->create(['is_active' => true]);
    $hotel->user->assignRole('manager');

    $this->actingAs($hotel->user)
        ->patchJson("/api/hotels/{$hotel->id}/status", [
            'is_active' => false,
        ])
        ->assertOk();

    // is_active مش معمول عليه cast لـ boolean بالموديل، فـ SQLite بيرجعها 0/1
    expect((bool) $hotel->fresh()->is_active)->toBeFalse();
});

it('admin can update status of any hotel', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $hotel = Hotel::factory()->create(['is_active' => true]);

    $this->actingAs($admin)
        ->patchJson("/api/hotels/{$hotel->id}/status", [
            'is_active' => false,
        ])
        ->assertOk();
});

it('non-owner cannot update hotel status', function () {
    $manager = User::factory()->create();
    $manager->assignRole('manager');

    $hotel = Hotel::factory()->create(['is_active' => true]);

    $this->actingAs($manager)
        ->patchJson("/api/hotels/{$hotel->id}/status", [
            'is_active' => false,
        ])
        ->assertForbidden();
});

it('guest cannot update hotel status', function () {
    $hotel = Hotel::factory()->create();

    $this->patchJson("/api/hotels/{$hotel->id}/status", [
        'is_active' => false,
    ])->assertUnauthorized();
});
