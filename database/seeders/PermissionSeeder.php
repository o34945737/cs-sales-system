<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        Permission::query()->where('name', 'manage users')->delete();

        $permissions = [
            'view dashboard',
            'access complaints',
            'access bad reviews',
            'access order trackings',
            'access oos',
            'view brands',
            'create brands',
            'update brands',
            'delete brands',
            'view platforms',
            'create platforms',
            'update platforms',
            'delete platforms',
            'view logistics',
            'create logistics',
            'update logistics',
            'delete logistics',
            'view sub cases',
            'create sub cases',
            'update sub cases',
            'delete sub cases',
            'view last steps',
            'create last steps',
            'update last steps',
            'delete last steps',
            'view users',
            'create users',
            'update users',
            'delete users',
        ];

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission);
        }

        $rolePermissions = [
            'Super Admin' => $permissions,
            'CS' => [
                'view dashboard',
                'access complaints',
                'access bad reviews',
            ],
            'Finance' => [
                'view dashboard',
                'access order trackings',
            ],
            'WH' => [
                'view dashboard',
                'access order trackings',
            ],
            'KAE' => [
                'view dashboard',
                'access complaints',
                'access oos',
            ],
            'After Sales' => [
                'view dashboard',
                'access complaints',
                'access bad reviews',
            ],
            'Brand' => [
                'view dashboard',
                'access complaints',
                'access bad reviews',
            ],
        ];

        foreach ($rolePermissions as $roleName => $assignedPermissions) {
            $role = Role::findOrCreate($roleName);
            $role->syncPermissions($assignedPermissions);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
