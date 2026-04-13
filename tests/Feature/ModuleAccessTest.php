<?php

use App\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Permission::findOrCreate('view dashboard');
    Permission::findOrCreate('access complaints');
    Permission::findOrCreate('access bad reviews');
    Permission::findOrCreate('access order trackings');
    Permission::findOrCreate('access oos');

    Role::findOrCreate('CS')->syncPermissions([
        'view dashboard',
        'access complaints',
        'access bad reviews',
    ]);

    Role::findOrCreate('WH')->syncPermissions([
        'view dashboard',
        'access order trackings',
    ]);
});

test('cs can access complaints but not order trackings', function () {
    $user = User::factory()->create();
    $user->assignRole('CS');

    $this->actingAs($user)
        ->get('/complaints')
        ->assertOk();

    $this->actingAs($user)
        ->get('/order-trackings')
        ->assertForbidden();
});

test('warehouse can access order trackings but not complaints', function () {
    $user = User::factory()->create();
    $user->assignRole('WH');

    $this->actingAs($user)
        ->get('/order-trackings')
        ->assertOk();

    $this->actingAs($user)
        ->get('/complaints')
        ->assertForbidden();
});
