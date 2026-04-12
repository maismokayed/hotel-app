<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Enums\RoleEnum;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
    Role::firstOrCreate([
    'name' => RoleEnum::ADMIN->value,
]);

Role::firstOrCreate([
    'name' => RoleEnum::MANAGER->value,
]);

Role::firstOrCreate([
    'name' => RoleEnum::USER->value,
]);
    }
}
