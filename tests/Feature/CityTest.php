<?php

use App\Models\City;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Database\Seeders\RoleSeeder;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

it('returns list of cities with bilingual names', function () {
    City::factory()->create([
        'name_ar' => 'حلب',
        'name_en' => 'Aleppo',
    ]);

    $response = $this->getJson('/api/cities');

    $response->assertOk()
        ->assertJsonFragment([
            'name' => [
                'ar' => 'حلب',
                'en' => 'Aleppo',
            ],
        ]);
});

it('returns image_url as null when city has no image', function () {
    $city = City::factory()->create();

    $this->getJson('/api/cities')
        ->assertOk()
        ->assertJsonFragment([
            'id' => $city->id,
        ]);
});

it('admin can upload an image to a city', function () {
    Storage::fake('public');

    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $city = City::factory()->create();
    $image = UploadedFile::fake()->image('city.jpg');

    $this->actingAs($admin)
        ->postJson("/api/cities/{$city->id}/image", [
            'image' => $image,
        ])->assertOk();

    expect($city->fresh()->getFirstMediaUrl('images'))->not->toBeEmpty();
});

it('non-admin cannot upload an image to a city', function () {
    $manager = User::factory()->create();
    $manager->assignRole('manager');

    $city = City::factory()->create();
    $image = UploadedFile::fake()->image('city.jpg');

    $this->actingAs($manager)
        ->postJson("/api/cities/{$city->id}/image", [
            'image' => $image,
        ])->assertForbidden();
});
