<?php

use App\Models\Room;
use App\Models\Hotel;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('returns room with translated type', function () {
    $room = Room::factory()->create([
        'type' => 'double',
    ]);

    $this->getJson("/api/rooms/{$room->id}")
        ->assertOk()
        ->assertJsonFragment([
            'type' => [
                'value' => 'double',
                'label' => [
                    'ar' => 'مزدوجة',
                    'en' => 'Double',
                ],
            ],
        ]);
});

it('lists rooms with hotel bilingual name', function () {
    $hotel = Hotel::factory()->create([
        'name_ar' => 'فندق تجريبي',
        'name_en' => 'Test Hotel',
    ]);

    Room::factory()->create(['hotel_id' => $hotel->id]);

    $response = $this->getJson('/api/rooms');

    $response->assertOk()
        ->assertJsonFragment([
            'name' => [
                'ar' => 'فندق تجريبي',
                'en' => 'Test Hotel',
            ],
        ]);
});
