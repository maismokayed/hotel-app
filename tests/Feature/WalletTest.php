<?php

use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    $this->seed(RoleSeeder::class);

    $this->user = User::factory()->create();
    $this->user->assignRole('user');

    // إنشاء المحفظة تلقائياً
    $this->wallet = Wallet::factory()->create([
        'user_id' => $this->user->id,
        'balance' => 0,
    ]);
});

// ============================================================
// SHOW
// ============================================================

it('user can view their wallet', function () {
    $response = $this->actingAs($this->user)
        ->getJson('/api/wallet');

    $response->assertOk()
        ->assertJsonStructure(['data' => ['id', 'balance']]);
});

it('cannot view wallet without token', function () {
    $response = $this->getJson('/api/wallet');
    $response->assertStatus(401);
});

// ============================================================
// DEPOSIT
// ============================================================

it('user can deposit money to wallet', function () {
    $response = $this->actingAs($this->user)
        ->postJson('/api/wallet/deposit', [
            'amount' => 100,
        ]);

    $response->assertOk()
        ->assertJsonPath('data.balance', '100.00');

    $this->assertDatabaseHas('wallets', [
        'user_id' => $this->user->id,
        'balance' => 100,
    ]);

    $this->assertDatabaseHas('wallet_transactions', [
        'wallet_id'        => $this->wallet->id,
        'amount'           => 100,
        'transaction_type' => 'credit',
        'reason'           => 'deposit',
    ]);
});

it('cannot deposit negative amount', function () {
    $response = $this->actingAs($this->user)
        ->postJson('/api/wallet/deposit', [
            'amount' => -50,
        ]);

    $response->assertStatus(422);
});

it('cannot deposit zero amount', function () {
    $response = $this->actingAs($this->user)
        ->postJson('/api/wallet/deposit', [
            'amount' => 0,
        ]);

    $response->assertStatus(422);
});

// ============================================================
// TRANSACTIONS
// ============================================================

it('user can view their transactions', function () {
    WalletTransaction::factory()->count(3)->create([
        'wallet_id' => $this->wallet->id,
        'user_id'   => $this->user->id,
    ]);

    $response = $this->actingAs($this->user)
        ->getJson('/api/wallet/transactions');

    $response->assertOk()
        ->assertJsonCount(3, 'data');
});

it('cannot view transactions without token', function () {
    $response = $this->getJson('/api/wallet/transactions');
    $response->assertStatus(401);
});
it('deposit shows the correct bilingual label', function () {
    WalletTransaction::factory()->create([
        'wallet_id'        => $this->wallet->id,
        'user_id'          => $this->user->id,
        'transaction_type' => 'credit',
        'reason'           => 'deposit',
    ]);

    $this->actingAs($this->user)
        ->getJson('/api/wallet/transactions')
        ->assertOk()
        ->assertJsonPath('data.0.reason', 'deposit')
        ->assertJsonPath('data.0.description.ar', 'عملية إيداع')
        ->assertJsonPath('data.0.description.en', 'Deposit');
});
it('paginates transactions with a default of 15 per page', function () {
    WalletTransaction::factory()->count(20)->create([
        'wallet_id' => $this->wallet->id,
        'user_id'   => $this->user->id,
    ]);

    $this->actingAs($this->user)
        ->getJson('/api/wallet/transactions')
        ->assertOk()
        ->assertJsonCount(15, 'data')
        ->assertJsonPath('meta.total', 20)
        ->assertJsonPath('meta.last_page', 2);
});

it('respects a custom per_page value', function () {
    WalletTransaction::factory()->count(20)->create([
        'wallet_id' => $this->wallet->id,
        'user_id'   => $this->user->id,
    ]);

    $this->actingAs($this->user)
        ->getJson('/api/wallet/transactions?per_page=5')
        ->assertOk()
        ->assertJsonCount(5, 'data')
        ->assertJsonPath('meta.last_page', 4);
});
it('payment shows the correct bilingual label', function () {
    WalletTransaction::factory()->create([
        'wallet_id'        => $this->wallet->id,
        'user_id'          => $this->user->id,
        'transaction_type' => 'debit',
        'reason'           => 'payment',
    ]);

    $this->actingAs($this->user)
        ->getJson('/api/wallet/transactions')
        ->assertOk()
        ->assertJsonPath('data.0.reason', 'payment')
        ->assertJsonPath('data.0.description.ar', 'عملية دفع')
        ->assertJsonPath('data.0.description.en', 'Payment');
});

it('refund shows the correct bilingual label', function () {
    WalletTransaction::factory()->create([
        'wallet_id'        => $this->wallet->id,
        'user_id'          => $this->user->id,
        'transaction_type' => 'credit',
        'reason'           => 'refund',
    ]);

    $this->actingAs($this->user)
        ->getJson('/api/wallet/transactions')
        ->assertOk()
        ->assertJsonPath('data.0.reason', 'refund')
        ->assertJsonPath('data.0.description.ar', 'عملية استرجاع')
        ->assertJsonPath('data.0.description.en', 'Refund');
});

it('falls back to type-based label for legacy transactions without a reason', function () {
    WalletTransaction::factory()->create([
        'wallet_id'        => $this->wallet->id,
        'user_id'          => $this->user->id,
        'transaction_type' => 'debit',
        'reason'           => null,
    ]);

    $this->actingAs($this->user)
        ->getJson('/api/wallet/transactions')
        ->assertOk()
        ->assertJsonPath('data.0.reason', null)
        ->assertJsonPath('data.0.description.ar', 'عملية دفع')
        ->assertJsonPath('data.0.description.en', 'Payment');
});
