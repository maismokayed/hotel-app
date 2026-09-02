<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;

uses(RefreshDatabase::class);

beforeEach(function () {
    app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    $this->seed(RolePermissionSeeder::class);
});

// ============================================================
// REGISTER TESTS
// ============================================================

it('can register a new user successfully', function () {
    $response = $this->withHeaders([
        'Accept' => 'application/json',
    ])->postJson('/api/register', [
        'full_name' => 'Ahmad Mohammad',
        'email'    => 'ahmad@example.com',
        'phone'    => '0933111222',
        'password' => 'Password123!',
    ]);
    $response->assertStatus(201)
        ->assertJson([
            'success' => true,
        ])
        ->assertJsonStructure([
            'success',
            'message' => [
                'ar',
                'en',
            ],
            'data' => [
                'user' => [
                    'id',
                    'full_name',
                    'email',
                    'phone',
                    'roles',
                ],
                'token',
            ],
        ]);

    $this->assertDatabaseHas('users', ['email' => 'ahmad@example.com']);
});

it('fails registration if email is already taken', function () {
    User::factory()->create(['email' => 'taken@example.com']);

    $response = $this->withHeaders([
        'Accept' => 'application/json',
    ])->postJson('/api/register', [
        'full_name' => 'New User',
        'email'    => 'taken@example.com',
        'phone'    => '0944555666',
        'password' => 'Password123!',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['email']);
});

// ============================================================
// LOGIN TESTS
// ============================================================

it('can login with correct credentials', function () {
    $user = User::factory()->create([
        'email'    => 'login@example.com',
        'password' => bcrypt('secret123'),
    ]);

    $response = $this->withHeaders([
        'Accept' => 'application/json',
    ])->postJson('/api/login', [
        'email'    => 'login@example.com',
        'password' => 'secret123',
    ]);

    $response->assertOk()
        ->assertJson([
            'success' => true,
        ])
        ->assertJsonStructure([
            'success',
            'message' => ['ar', 'en'],
            'data' => [
                'user',
                'token',
            ],
        ]);
});

it('returns 401 for wrong credentials', function () {
    User::factory()->create([
        'email' => 'user@example.com',
        'password' => bcrypt('correct_password'),
    ]);

    $response = $this->withHeaders([
        'Accept' => 'application/json',
    ])->postJson('/api/login', [
        'email'    => 'user@example.com',
        'password' => 'wrong_password',
    ]);

    $response->assertStatus(401)
        ->assertJson([
            'success' => false,
        ])
        ->assertJsonStructure([
            'success',
            'message' => ['ar', 'en'],
        ]);
});

// ============================================================
// PROFILE & LOGOUT TESTS
// ============================================================

it('can fetch authenticated user profile', function () {
    $user = User::factory()->create();
    $user->assignRole('user');

    $response = $this->actingAs($user)
        ->getJson('/api/profile');

    $response->assertOk()
        ->assertJson([
            'success' => true,
        ])
        ->assertJsonPath('data.user.email', $user->email)
        ->assertJsonPath('data.user.roles.0', 'user');
});

it('cannot access profile without token', function () {
    $response = $this->getJson('/api/profile');

    $response->assertStatus(401);
});

it('can logout successfully', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->postJson('/api/logout');

    $response->assertOk()
        ->assertJson([
            'success' => true,
        ])
        ->assertJsonStructure([
            'success',
            'message' => ['ar', 'en'],
        ]);

    // التحقق من حذف التوكنات
    expect($user->tokens)->toBeEmpty();
});
