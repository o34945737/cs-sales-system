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
            'view complaint sources',
            'create complaint sources',
            'update complaint sources',
            'delete complaint sources',
            'view complaint powers',
            'create complaint powers',
            'update complaint powers',
            'delete complaint powers',
            'view complaint step statuses',
            'create complaint step statuses',
            'update complaint step statuses',
            'delete complaint step statuses',
            'view sku codes',
            'create sku codes',
            'update sku codes',
            'delete sku codes',
            'view sub cases',
            'create sub cases',
            'update sub cases',
            'delete sub cases',
            'view last steps',
            'create last steps',
            'update last steps',
            'delete last steps',
            'view reason whitelists',
            'create reason whitelists',
            'update reason whitelists',
            'delete reason whitelists',
            'view reason late responses',
            'create reason late responses',
            'update reason late responses',
            'delete reason late responses',
            'view order tracking data sources',
            'create order tracking data sources',
            'update order tracking data sources',
            'delete order tracking data sources',
            'view order tracking erp statuses',
            'create order tracking erp statuses',
            'update order tracking erp statuses',
            'delete order tracking erp statuses',
            'view order tracking rgo entries',
            'create order tracking rgo entries',
            'update order tracking rgo entries',
            'delete order tracking rgo entries',
            'view jet track entries',
            'create jet track entries',
            'update jet track entries',
            'delete jet track entries',
            'view oos reasons',
            'create oos reasons',
            'update oos reasons',
            'delete oos reasons',
            'view oos solutions',
            'create oos solutions',
            'update oos solutions',
            'delete oos solutions',
            'view cause bys',
            'create cause bys',
            'update cause bys',
            'delete cause bys',
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
