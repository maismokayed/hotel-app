<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    { 
        Role::firstOrCreate(['name' => 'super-admin']);
        Role::firstOrCreate(['name' => 'hotel-owner']);
        Role::firstOrCreate(['name' => 'hotel-admin']);
        Role::firstOrCreate(['name' => 'user']);
    }
}
