<?php

use App\Models\User;
use App\Models\Hotel;
use App\Models\Room;
use App\Models\Booking;
use App\Models\Review;
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

    $this->hotel = Hotel::factory()->create();
    $this->room  = Room::factory()->create(['hotel_id' => $this->hotel->id]);

    $this->booking = Booking::factory()->create([
        'user_id' => $this->user->id,
        'room_id' => $this->room->id,
        'status'  => 'completed',
    ]);
});

// ============================================================
// INDEX
// ============================================================

it('anyone can view hotel reviews', function () {
    Review::factory()->count(3)->create([
        'hotel_id'   => $this->hotel->id,
        'user_id'    => $this->user->id,
        'booking_id' => $this->booking->id,
    ]);

    $response = $this->getJson("/api/hotels/{$this->hotel->id}/reviews");

    $response->assertOk()
             ->assertJsonCount(3, 'data');
});

// ============================================================
// STORE
// ============================================================

it('user can create a review for completed booking', function () {
    $response = $this->actingAs($this->user)
        ->postJson('/api/reviews', [
            'hotel_id'   => $this->hotel->id,
            'booking_id' => $this->booking->id,
            'comment'    => 'فندق رائع!',
            'rating'     => 5,
        ]);

    $response->assertStatus(201)
             ->assertJsonStructure(['data' => ['id', 'comment', 'rating']]);

    $this->assertDatabaseHas('reviews', [
        'booking_id' => $this->booking->id,
        'rating'     => 5,
    ]);
});

it('user cannot review a pending booking', function () {
    $pendingBooking = Booking::factory()->create([
        'user_id' => $this->user->id,
        'room_id' => $this->room->id,
        'status'  => 'pending',
    ]);

    $response = $this->actingAs($this->user)
        ->postJson('/api/reviews', [
            'hotel_id'   => $this->hotel->id,
            'booking_id' => $pendingBooking->id,
            'comment'    => 'تجربة سيئة',
            'rating'     => 2,
        ]);

    $response->assertStatus(422)
             ->assertJson(['message' => 'يمكنك التقييم فقط بعد اكتمال الحجز.']);
});

it('user cannot review the same booking twice', function () {
    Review::factory()->create([
        'user_id'    => $this->user->id,
        'hotel_id'   => $this->hotel->id,
        'booking_id' => $this->booking->id,
    ]);

    $response = $this->actingAs($this->user)
        ->postJson('/api/reviews', [
            'hotel_id'   => $this->hotel->id,
            'booking_id' => $this->booking->id,
            'comment'    => 'تقييم ثاني',
            'rating'     => 3,
        ]);

    $response->assertStatus(422)
             ->assertJson(['message' => 'لقد قمت بتقييم هذا الحجز مسبقاً.']);
});

it('user cannot review another user booking', function () {
    $otherUser    = User::factory()->create();
    $otherBooking = Booking::factory()->create([
        'user_id' => $otherUser->id,
        'room_id' => $this->room->id,
        'status'  => 'completed',
    ]);

    $response = $this->actingAs($this->user)
        ->postJson('/api/reviews', [
            'hotel_id'   => $this->hotel->id,
            'booking_id' => $otherBooking->id,
            'comment'    => 'تقييم',
            'rating'     => 3,
        ]);

    $response->assertStatus(403);
});

// ============================================================
// DESTROY
// ============================================================

it('admin can delete a review', function () {
    $review = Review::factory()->create([
        'user_id'    => $this->user->id,
        'hotel_id'   => $this->hotel->id,
        'booking_id' => $this->booking->id,
    ]);

    $response = $this->actingAs($this->admin)
        ->deleteJson("/api/reviews/{$review->id}");

    $response->assertOk()
             ->assertJson(['message' => 'تم حذف التقييم بنجاح.']);

    $this->assertDatabaseMissing('reviews', ['id' => $review->id]);
});

it('user cannot delete a review', function () {
    $review = Review::factory()->create([
        'user_id'    => $this->user->id,
        'hotel_id'   => $this->hotel->id,
        'booking_id' => $this->booking->id,
    ]);

    $response = $this->actingAs($this->user)
        ->deleteJson("/api/reviews/{$review->id}");

    $response->assertStatus(403);
});

// ============================================================
// GUEST
// ============================================================

it('cannot create review without token', function () {
    $response = $this->postJson('/api/reviews', []);
    $response->assertStatus(401);
});