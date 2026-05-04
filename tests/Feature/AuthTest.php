<?php
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Database\Seeders\RoleSeeder;
uses(RefreshDatabase::class);



beforeEach(function () {
     app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions(); 
    $this->seed(RoleSeeder::class);
    });
// ============================================================
// REGISTER TESTS
// ============================================================

it('can register a new user successfully', function () {
    $response = $this->withHeaders([
    'Accept' => 'application/json',
])->postJson('/api/register', [
        'full_name' => 'Ahmad Mohammad',
        'email'     => 'ahmad@example.com',
        'phone'     => '0933111222',
        'password'  => 'Password123!',
    ]);

    $response->assertStatus(200) // أو 201 إذا عدلتها في الكنترولر
             ->assertJsonStructure([
                 'user' => ['id', 'full_name', 'email', 'phone', 'roles'],
                 'token'
             ]);

    $this->assertDatabaseHas('users', ['email' => 'ahmad@example.com']);
});

it('fails registration if email is already taken', function () {
    User::factory()->create(['email' => 'taken@example.com']);

    $response = $this->withHeaders([
    'Accept' => 'application/json',
])->postJson('/api/register', [
        'full_name' => 'New User',
        'email'     => 'taken@example.com',
        'phone'     => '0944555666',
        'password'  => 'Password123!',
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
             ->assertJsonStructure(['user', 'token']);
});
it('returns 401 for wrong credentials', function () {
    $user = User::factory()->create([
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
                 'message' => 'البريد الإلكتروني أو كلمة المرور غير صحيحة.'
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
             ->assertJsonPath('user.email', $user->email)
             ->assertJsonPath('user.roles.0', 'user');
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
             ->assertJson(['message' => 'تم تسجيل الخروج بنجاح.']);
             
    // نتحقق أن التوكن تم حذفه من القاعدة
    expect($user->tokens)->toBeEmpty();
});