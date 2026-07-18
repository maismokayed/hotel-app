<?php

use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Database\Seeders\RoleSeeder;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

it('returns list of services with bilingual names', function () {
    Service::factory()->create([
        'name_ar' => 'واي فاي',
        'name_en' => 'WiFi',
    ]);

    $this->getJson('/api/services')
        ->assertOk()
        ->assertJsonFragment([
            'name' => [
                'ar' => 'واي فاي',
                'en' => 'WiFi',
            ],
        ]);
});

it('admin can create a service', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $this->actingAs($admin)
        ->postJson('/api/services', [
            'name_ar' => 'مسبح',
            'name_en' => 'Pool',
        ])->assertCreated();

    $this->assertDatabaseHas('services', [
        'name_ar' => 'مسبح',
        'name_en' => 'Pool',
    ]);
});

it('non-admin cannot create a service', function () {
    $manager = User::factory()->create();
    $manager->assignRole('manager');

    $this->actingAs($manager)
        ->postJson('/api/services', [
            'name_ar' => 'مسبح',
            'name_en' => 'Pool',
        ])->assertForbidden();
});

it('rejects duplicate service name', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    Service::factory()->create([
        'name_ar' => 'مسبح',
        'name_en' => 'Pool',
    ]);

    $this->actingAs($admin)
        ->postJson('/api/services', [
            'name_ar' => 'مسبح',
            'name_en' => 'Pool 2',
        ])->assertUnprocessable();
});

it('admin can update a service', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $service = Service::factory()->create();

    $this->actingAs($admin)
        ->putJson("/api/services/{$service->id}", [
            'name_ar' => 'محدث',
            'name_en' => 'Updated',
        ])->assertOk();

    $this->assertDatabaseHas('services', [
        'id' => $service->id,
        'name_ar' => 'محدث',
        'name_en' => 'Updated',
    ]);
});

it('admin can delete a service', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $service = Service::factory()->create();

    $this->actingAs($admin)
        ->deleteJson("/api/services/{$service->id}")
        ->assertOk();

    $this->assertDatabaseMissing('services', ['id' => $service->id]);
});
