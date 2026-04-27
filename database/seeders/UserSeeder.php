<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
     // Admin
    $admin = User::create([
        'full_name' => 'Admin User',
        'email' => 'admin@test.com',
        'phone' => '0000000000',
        'password' => Hash::make('password1'),
    ]);

    $admin->assignRole('admin');

    // Normal User
    $user = User::create([
        'full_name' => 'Normal User',
        'email' => 'user@test.com',
        'phone' => '1111111111',
        'password' => Hash::make('password2'),
    ]);

    $user->assignRole('user');
    }
}
