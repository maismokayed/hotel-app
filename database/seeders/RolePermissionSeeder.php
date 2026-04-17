<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Enums\RoleEnum;
use App\Enums\PermissionEnum;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
    // 1. Create Permissions
        foreach (PermissionEnum::cases() as $permission) {
            Permission::firstOrCreate([
                'name' => $permission->value,
                'guard_name' => 'api'
            ]);
        }

        // 2. Create Roles
        foreach (RoleEnum::cases() as $role) {
            Role::firstOrCreate([
                'name' => $role->value,
                'guard_name' => 'api'
            ]);
        }

        // 3. Admin → كل الصلاحيات
        $adminRole = Role::where('name', RoleEnum::ADMIN->value)->first();
        $adminRole->syncPermissions(Permission::all());

        // 4. Manager → إدارة الفندق تبعه
        $managerRole = Role::where('name', RoleEnum::MANAGER->value)->first();
        $managerPermissions = [
            PermissionEnum::VIEW_HOTELS->value,
            PermissionEnum::CREATE_HOTELS->value,
            PermissionEnum::UPDATE_HOTELS->value,
            PermissionEnum::MANAGE_OWN_HOTEL->value,

            PermissionEnum::VIEW_ROOMS->value,
            PermissionEnum::CREATE_ROOMS->value,
            PermissionEnum::UPDATE_ROOMS->value,
            PermissionEnum::MANAGE_OWN_ROOMS->value,

            PermissionEnum::VIEW_BOOKINGS->value,

            PermissionEnum::UPLOAD_MEDIA->value,
            PermissionEnum::DELETE_MEDIA->value,
        ];
        $managerRole->syncPermissions($managerPermissions);

        // 5. User → استخدام التطبيق فقط
        $userRole = Role::where('name', RoleEnum::USER->value)->first();
        $userPermissions = [
            PermissionEnum::VIEW_HOTELS->value,
            PermissionEnum::VIEW_ROOMS->value,

            PermissionEnum::CREATE_BOOKINGS->value,
            PermissionEnum::VIEW_OWN_BOOKINGS->value,
            PermissionEnum::CANCEL_BOOKINGS->value,

            PermissionEnum::VIEW_OWN_WALLET->value,
            PermissionEnum::VIEW_OWN_WALLET_TRANSACTIONS->value,
            PermissionEnum::TOP_UP_WALLET->value,

            PermissionEnum::VIEW_REVIEWS->value,
            PermissionEnum::CREATE_REVIEWS->value,

            PermissionEnum::APPLY_COUPON->value,

            PermissionEnum::VIEW_OWN_PROFILE->value,
            PermissionEnum::UPDATE_OWN_PROFILE->value,
        ];
        $userRole->syncPermissions($userPermissions);
    }
}
