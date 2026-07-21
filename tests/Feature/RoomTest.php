<?php

use App\Models\Hotel;
use App\Models\Room;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Database\Seeders\RoleSeeder;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed([
        RoleSeeder::class,
    ]);
});

// ============================================================
// INDEX (كل الغرف بكل الفنادق)
// ============================================================

it('returns all rooms with hotel info loaded', function () {
    Room::factory()->create();
    Room::factory()->create();

    $this->getJson('/api/rooms')
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('data.0.hotel.id', fn($id) => $id !== null);
});

it('returns empty array when no rooms exist', function () {
    $this->getJson('/api/rooms')
        ->assertOk()
        ->assertJsonCount(0, 'data');
});

it('lists rooms with hotel bilingual name', function () {
    $hotel = Hotel::factory()->create([
        'name_ar' => 'فندق تجريبي',
        'name_en' => 'Test Hotel',
    ]);

    Room::factory()->create(['hotel_id' => $hotel->id]);

    $this->getJson('/api/rooms')
        ->assertOk()
        ->assertJsonFragment([
            'name' => ['ar' => 'فندق تجريبي', 'en' => 'Test Hotel'],
        ]);
});

// ============================================================
// SHOW
// ============================================================

it('shows a single room with hotel info', function () {
    $hotel = Hotel::factory()->create();
    $room = Room::factory()->create(['hotel_id' => $hotel->id]);

    $this->getJson("/api/rooms/{$room->id}")
        ->assertOk()
        ->assertJsonPath('data.id', $room->id)
        ->assertJsonPath('data.hotel.id', $hotel->id);
});

it('returns 404 for a non-existent room', function () {
    $this->getJson('/api/rooms/999999')
        ->assertNotFound();
});

it('returns 404 for a non-numeric room id', function () {
    $this->getJson('/api/rooms/abc')
        ->assertNotFound();
});

it('returns room with translated type', function () {
    $room = Room::factory()->create(['type' => 'double']);

    $this->getJson("/api/rooms/{$room->id}")
        ->assertOk()
        ->assertJsonFragment([
            'type' => [
                'value' => 'double',
                'label' => ['ar' => 'مزدوجة', 'en' => 'Double'],
            ],
        ]);
});

// ============================================================
// INDEX BY HOTEL (/hotels/{hotel}/rooms)
// ============================================================

it('returns only rooms belonging to the given hotel', function () {
    $hotelA = Hotel::factory()->create();
    $hotelB = Hotel::factory()->create();

    Room::factory()->count(2)->create(['hotel_id' => $hotelA->id]);
    Room::factory()->create(['hotel_id' => $hotelB->id]);

    $this->getJson("/api/hotels/{$hotelA->id}/rooms")
        ->assertOk()
        ->assertJsonCount(2, 'data');
});

it('returns an empty list for a hotel with no rooms', function () {
    $hotel = Hotel::factory()->create();

    $this->getJson("/api/hotels/{$hotel->id}/rooms")
        ->assertOk()
        ->assertJsonCount(0, 'data');
});

it('returns 404 when listing rooms for a non-existent hotel', function () {
    $this->getJson('/api/hotels/999999/rooms')
        ->assertNotFound();
});

// ============================================================
// ROOM TYPES (/hotels/{hotel}/room-types) — لواجهة الزبون
// ============================================================

it('returns 404 when getting room-types for a non-existent hotel', function () {
    $this->getJson('/api/hotels/999999/room-types')
        ->assertNotFound();
});

it('groups only available rooms by type', function () {
    $hotel = Hotel::factory()->create();

    Room::factory()->count(3)->create([
        'hotel_id' => $hotel->id,
        'type'     => 'single',
        'status'   => 'available',
    ]);
    Room::factory()->count(2)->create([
        'hotel_id' => $hotel->id,
        'type'     => 'double',
        'status'   => 'available',
    ]);

    $response = $this->getJson("/api/hotels/{$hotel->id}/room-types")
        ->assertOk();

    $response->assertJsonCount(2, 'data');
    expect(
        collect($response->json('data'))->firstWhere('type.value', 'single')['available_count']
    )->toBe(3);
});

it('excludes room types that have no available rooms', function () {
    $hotel = Hotel::factory()->create();

    // كل غرف النوع suite مشغولة أو بالصيانة، ما في ولا وحدة available
    Room::factory()->create(['hotel_id' => $hotel->id, 'type' => 'suite', 'status' => 'booked']);
    Room::factory()->create(['hotel_id' => $hotel->id, 'type' => 'suite', 'status' => 'maintenance']);
    Room::factory()->create(['hotel_id' => $hotel->id, 'type' => 'single', 'status' => 'available']);

    $response = $this->getJson("/api/hotels/{$hotel->id}/room-types")
        ->assertOk();

    $response->assertJsonCount(1, 'data');
    expect(collect($response->json('data'))->pluck('type.value'))->not->toContain('suite');
});

it('returns empty data when no rooms are available at all', function () {
    $hotel = Hotel::factory()->create();

    Room::factory()->create(['hotel_id' => $hotel->id, 'status' => 'booked']);

    $this->getJson("/api/hotels/{$hotel->id}/room-types")
        ->assertOk()
        ->assertJsonCount(0, 'data');
});

it('returns null image_url when no room in the type group has an image', function () {
    $hotel = Hotel::factory()->create();
    Room::factory()->create([
        'hotel_id' => $hotel->id,
        'type'     => 'single',
        'status'   => 'available',
    ]);

    $response = $this->getJson("/api/hotels/{$hotel->id}/room-types")
        ->assertOk();

    expect(collect($response->json('data'))->firstWhere('type.value', 'single')['image_url'])
        ->toBeNull();
});

it('returns an image_url from any room in the group that has one', function () {
    Storage::fake('public');

    $hotel = Hotel::factory()->create();
    Room::factory()->create(['hotel_id' => $hotel->id, 'type' => 'single', 'status' => 'available']);
    $roomWithImage = Room::factory()->create(['hotel_id' => $hotel->id, 'type' => 'single', 'status' => 'available']);

    $roomWithImage->addMedia(UploadedFile::fake()->image('room.jpg'))
        ->toMediaCollection('images');

    $response = $this->getJson("/api/hotels/{$hotel->id}/room-types")
        ->assertOk();

    expect(collect($response->json('data'))->firstWhere('type.value', 'single')['image_url'])
        ->not->toBeNull();
});

// ============================================================
// STORE
// ============================================================

it('admin can create a room for any hotel', function () {
    $hotel = Hotel::factory()->create();
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $this->actingAs($admin)
        ->postJson('/api/rooms', [
            'hotel_id'        => $hotel->id,
            'room_number'     => '101',
            'type'            => 'single',
            'capacity'        => 1,
            'price_per_night' => 150,
        ])
        ->assertCreated();
});

it('owner manager can create a room for their own hotel', function () {
    $hotel = Hotel::factory()->create();
    $hotel->user->assignRole('manager');

    $this->actingAs($hotel->user)
        ->postJson('/api/rooms', [
            'hotel_id'        => $hotel->id,
            'room_number'     => '102',
            'type'            => 'double',
            'capacity'        => 2,
            'price_per_night' => 300,
        ])
        ->assertCreated();
});

it('manager cannot create a room for a hotel they do not own', function () {
    $hotel = Hotel::factory()->create();

    $manager = User::factory()->create();
    $manager->assignRole('manager');

    $this->actingAs($manager)
        ->postJson('/api/rooms', [
            'hotel_id'        => $hotel->id,
            'room_number'     => '103',
            'type'            => 'single',
            'capacity'        => 1,
            'price_per_night' => 150,
        ])
        ->assertForbidden();
});

it('guest cannot create a room', function () {
    $hotel = Hotel::factory()->create();

    $this->postJson('/api/rooms', [
        'hotel_id'        => $hotel->id,
        'room_number'     => '104',
        'type'            => 'single',
        'capacity'        => 1,
        'price_per_night' => 150,
    ])->assertUnauthorized();
});

it('defaults status to available when not provided', function () {
    $hotel = Hotel::factory()->create();
    $hotel->user->assignRole('manager');

    $this->actingAs($hotel->user)
        ->postJson('/api/rooms', [
            'hotel_id'        => $hotel->id,
            'room_number'     => '105',
            'type'            => 'single',
            'capacity'        => 1,
            'price_per_night' => 150,
        ])
        ->assertCreated()
        ->assertJsonPath('data.status', 'available');
});

it('rejects missing required fields on store', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $this->actingAs($admin)
        ->postJson('/api/rooms', [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['hotel_id', 'room_number', 'type', 'capacity', 'price_per_night']);
});

it('rejects an invalid room type', function () {
    $hotel = Hotel::factory()->create();
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $this->actingAs($admin)
        ->postJson('/api/rooms', [
            'hotel_id'        => $hotel->id,
            'room_number'     => '106',
            'type'            => 'penthouse',
            'capacity'        => 1,
            'price_per_night' => 150,
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['type']);
});

it('rejects capacity outside allowed range', function () {
    $hotel = Hotel::factory()->create();
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $this->actingAs($admin)
        ->postJson('/api/rooms', [
            'hotel_id'        => $hotel->id,
            'room_number'     => '107',
            'type'            => 'single',
            'capacity'        => 20,
            'price_per_night' => 150,
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['capacity']);
});

it('rejects a negative price_per_night', function () {
    $hotel = Hotel::factory()->create();
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $this->actingAs($admin)
        ->postJson('/api/rooms', [
            'hotel_id'        => $hotel->id,
            'room_number'     => '108',
            'type'            => 'single',
            'capacity'        => 1,
            'price_per_night' => -50,
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['price_per_night']);
});

it('rejects a non-existent hotel_id', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $this->actingAs($admin)
        ->postJson('/api/rooms', [
            'hotel_id'        => 999999,
            'room_number'     => '109',
            'type'            => 'single',
            'capacity'        => 1,
            'price_per_night' => 150,
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['hotel_id']);
});

it('rejects room type with wrong casing', function () {
    $hotel = Hotel::factory()->create();
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $this->actingAs($admin)
        ->postJson('/api/rooms', [
            'hotel_id'        => $hotel->id,
            'room_number'     => '998',
            'type'            => 'Single', // بحرف كبير - لازم يترفض لأنو enum حساس لحالة الأحرف
            'capacity'        => 1,
            'price_per_night' => 50,
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['type']);
});

it('rejects room_number longer than 50 characters', function () {
    $hotel = Hotel::factory()->create();
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $this->actingAs($admin)
        ->postJson('/api/rooms', [
            'hotel_id'        => $hotel->id,
            'room_number'     => str_repeat('A', 51),
            'type'            => 'single',
            'capacity'        => 1,
            'price_per_night' => 50,
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['room_number']);
});

it('rejects a non-numeric price', function () {
    $hotel = Hotel::factory()->create();
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $this->actingAs($admin)
        ->postJson('/api/rooms', [
            'hotel_id'        => $hotel->id,
            'room_number'     => '106',
            'type'            => 'single',
            'capacity'        => 1,
            'price_per_night' => 'free',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['price_per_night']);
});

// ============================================================
// UPDATE
// ============================================================

it('returns 404 when updating a non-existent room', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $this->actingAs($admin)
        ->putJson('/api/rooms/999999', ['capacity' => 3])
        ->assertNotFound();
});

it('owner can update their room', function () {
    $hotel = Hotel::factory()->create();
    $hotel->user->assignRole('manager');
    $room = Room::factory()->create(['hotel_id' => $hotel->id]);

    $this->actingAs($hotel->user)
        ->putJson("/api/rooms/{$room->id}", [
            'price_per_night' => 400,
        ])
        ->assertOk()
        ->assertJsonPath('data.price_per_night', 400);
});

it('admin can update any room', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $room = Room::factory()->create();

    $this->actingAs($admin)
        ->putJson("/api/rooms/{$room->id}", [
            'status' => 'maintenance',
        ])
        ->assertOk()
        ->assertJsonPath('data.status', 'maintenance');
});

it('manager cannot update a room in a hotel they do not own', function () {
    $manager = User::factory()->create();
    $manager->assignRole('manager');

    $room = Room::factory()->create();

    $this->actingAs($manager)
        ->putJson("/api/rooms/{$room->id}", [
            'price_per_night' => 999,
        ])
        ->assertForbidden();
});

it('guest cannot update a room', function () {
    $room = Room::factory()->create();

    $this->putJson("/api/rooms/{$room->id}", [
        'price_per_night' => 999,
    ])->assertUnauthorized();
});

it('rejects an invalid type on update', function () {
    $hotel = Hotel::factory()->create();
    $hotel->user->assignRole('manager');
    $room = Room::factory()->create(['hotel_id' => $hotel->id]);

    $this->actingAs($hotel->user)
        ->putJson("/api/rooms/{$room->id}", [
            'type' => 'penthouse',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['type']);
});

// ============================================================
// DESTROY
// ============================================================

it('owner can delete their room', function () {
    $hotel = Hotel::factory()->create();
    $hotel->user->assignRole('manager');
    $room = Room::factory()->create(['hotel_id' => $hotel->id]);

    $this->actingAs($hotel->user)
        ->deleteJson("/api/rooms/{$room->id}")
        ->assertOk();

    $this->assertDatabaseMissing('rooms', ['id' => $room->id]);
});

it('admin can delete any room', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $room = Room::factory()->create();

    $this->actingAs($admin)
        ->deleteJson("/api/rooms/{$room->id}")
        ->assertOk();

    $this->assertDatabaseMissing('rooms', ['id' => $room->id]);
});

it('manager cannot delete a room in a hotel they do not own', function () {
    $manager = User::factory()->create();
    $manager->assignRole('manager');

    $room = Room::factory()->create();

    $this->actingAs($manager)
        ->deleteJson("/api/rooms/{$room->id}")
        ->assertForbidden();

    $this->assertDatabaseHas('rooms', ['id' => $room->id]);
});

it('guest cannot delete a room', function () {
    $room = Room::factory()->create();

    $this->deleteJson("/api/rooms/{$room->id}")
        ->assertUnauthorized();
});

// ============================================================
// IMAGES
// ============================================================

it('rejects image upload without a file', function () {
    $hotel = Hotel::factory()->create();
    $hotel->user->assignRole('manager');
    $room = Room::factory()->create(['hotel_id' => $hotel->id]);

    $this->actingAs($hotel->user)
        ->postJson("/api/rooms/{$room->id}/images", [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['image']);
});

it('owner can upload a room image', function () {
    Storage::fake('public');

    $hotel = Hotel::factory()->create();
    $hotel->user->assignRole('manager');
    $room = Room::factory()->create(['hotel_id' => $hotel->id]);

    $this->actingAs($hotel->user)
        ->postJson("/api/rooms/{$room->id}/images", [
            'image' => UploadedFile::fake()->image('room.jpg'),
        ])
        ->assertOk()
        ->assertJsonStructure(['id', 'url']);

    expect($room->fresh()->getMedia('images'))->toHaveCount(1);
});

it('allows uploading multiple images to the same room (documents current behavior)', function () {
    Storage::fake('public');

    $hotel = Hotel::factory()->create();
    $hotel->user->assignRole('manager');
    $room = Room::factory()->create(['hotel_id' => $hotel->id]);

    $this->actingAs($hotel->user)
        ->postJson("/api/rooms/{$room->id}/images", ['image' => UploadedFile::fake()->image('a.jpg')]);
    $this->actingAs($hotel->user)
        ->postJson("/api/rooms/{$room->id}/images", ['image' => UploadedFile::fake()->image('b.jpg')]);

    // هاد التست بيوثق السلوك الحالي (تراكم صور بدون استبدال) — مش بالضرورة السلوك المرغوب
    // لو بدك تسمحي بصورة وحيدة بس للغرفة، لازم تضيفي singleFile() بالـ registerMediaCollections
    expect($room->fresh()->getMedia('images'))->toHaveCount(2);
});

it('rejects a non-image file on room image upload', function () {
    Storage::fake('public');

    $hotel = Hotel::factory()->create();
    $hotel->user->assignRole('manager');
    $room = Room::factory()->create(['hotel_id' => $hotel->id]);

    $this->actingAs($hotel->user)
        ->postJson("/api/rooms/{$room->id}/images", [
            'image' => UploadedFile::fake()->create('document.pdf', 100),
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['image']);
});

it('manager cannot upload an image for a room they do not own', function () {
    Storage::fake('public');

    $manager = User::factory()->create();
    $manager->assignRole('manager');

    $room = Room::factory()->create();

    $this->actingAs($manager)
        ->postJson("/api/rooms/{$room->id}/images", [
            'image' => UploadedFile::fake()->image('room.jpg'),
        ])
        ->assertForbidden();
});

it('guest cannot upload a room image', function () {
    $room = Room::factory()->create();

    $this->postJson("/api/rooms/{$room->id}/images", [
        'image' => UploadedFile::fake()->image('room.jpg'),
    ])->assertUnauthorized();
});

it('owner can delete a room image', function () {
    Storage::fake('public');

    $hotel = Hotel::factory()->create();
    $hotel->user->assignRole('manager');
    $room = Room::factory()->create(['hotel_id' => $hotel->id]);

    $media = $room->addMedia(UploadedFile::fake()->image('room.jpg'))
        ->toMediaCollection('images');

    $this->actingAs($hotel->user)
        ->deleteJson("/api/rooms/{$room->id}/images/{$media->id}")
        ->assertOk();

    expect($room->fresh()->getMedia('images'))->toHaveCount(0);
});

it('returns 404 when deleting a non-existent room image', function () {
    $hotel = Hotel::factory()->create();
    $hotel->user->assignRole('manager');
    $room = Room::factory()->create(['hotel_id' => $hotel->id]);

    $this->actingAs($hotel->user)
        ->deleteJson("/api/rooms/{$room->id}/images/999999")
        ->assertNotFound();
});

it('returns 404 when deleting media that belongs to a different room', function () {
    Storage::fake('public');

    $hotel = Hotel::factory()->create();
    $hotel->user->assignRole('manager');
    $roomA = Room::factory()->create(['hotel_id' => $hotel->id]);
    $roomB = Room::factory()->create(['hotel_id' => $hotel->id]);

    $media = $roomA->addMedia(UploadedFile::fake()->image('a.jpg'))
        ->toMediaCollection('images');

    // محاولة حذف صورة roomA من خلال roomB — لازم يفشل (404) حتى لو نفس المالك
    $this->actingAs($hotel->user)
        ->deleteJson("/api/rooms/{$roomB->id}/images/{$media->id}")
        ->assertNotFound();

    expect($roomA->fresh()->getMedia('images'))->toHaveCount(1);
});

it('manager cannot delete an image for a room they do not own', function () {
    Storage::fake('public');

    $manager = User::factory()->create();
    $manager->assignRole('manager');

    $room = Room::factory()->create();
    $media = $room->addMedia(UploadedFile::fake()->image('room.jpg'))
        ->toMediaCollection('images');

    $this->actingAs($manager)
        ->deleteJson("/api/rooms/{$room->id}/images/{$media->id}")
        ->assertForbidden();
});

it('shows uploaded images with id and url via RoomResource', function () {
    Storage::fake('public');

    $hotel = Hotel::factory()->create();
    $hotel->user->assignRole('manager');
    $room = Room::factory()->create(['hotel_id' => $hotel->id]);

    $room->addMedia(UploadedFile::fake()->image('room1.jpg'))->toMediaCollection('images');
    $room->addMedia(UploadedFile::fake()->image('room2.jpg'))->toMediaCollection('images');

    $this->getJson("/api/rooms/{$room->id}")
        ->assertOk()
        ->assertJsonCount(2, 'data.images')
        ->assertJsonStructure(['data' => ['images' => [['id', 'url']]]]);
});

// ============================================================
// حالات "بيانات فاسدة" (Data integrity) — مش من خلال الـ API
// ============================================================

it('documents that a corrupted type value in the database breaks the show endpoint', function () {
    $hotel = Hotel::factory()->create();

    // إدخال قيمة غير صالحة مباشرة بالداتابيز (متجاوزين الـ Eloquent cast والـ validation)
    // بيحاكي بيانات قديمة/فاسدة كانت موجودة قبل تفعيل الـ enum الحالي
    $roomId = DB::table('rooms')->insertGetId([
        'hotel_id'        => $hotel->id,
        'room_number'     => '999',
        'type'            => 'twin', // قيمة قديمة مش موجودة بالـ enum الحالي
        'capacity'        => 1,
        'price_per_night' => 50,
        'status'          => 'available',
        'created_at'      => now(),
        'updated_at'      => now(),
    ]);

    // هاد التست بيوثق إنو حالياً بيرجع 500 لو صار هيك — نقطة ضعف معروفة بالكود
    // (الحل المقترح: إضافة try/catch أو fallback بالـ RoomType enum cast)
    $this->getJson("/api/rooms/{$roomId}")
        ->assertStatus(500);
});
