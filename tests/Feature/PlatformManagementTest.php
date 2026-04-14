<?php

use App\Models\Platform;
use App\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Permission::findOrCreate('view platforms');
    Permission::findOrCreate('create platforms');
    Permission::findOrCreate('update platforms');
    Permission::findOrCreate('delete platforms');

    Role::findOrCreate('Super Admin')->syncPermissions([
        'view platforms',
        'create platforms',
        'update platforms',
        'delete platforms',
    ]);

    Role::findOrCreate('CS');
});

test('super admin can visit the platform management page', function () {
    $admin = User::factory()->create();
    $admin->assignRole('Super Admin');

    $response = $this->actingAs($admin)->get('/platforms');

    $response->assertOk();
});

test('non admin users cannot visit the platform management page', function () {
    $user = User::factory()->create();
    $user->assignRole('CS');

    $response = $this->actingAs($user)->get('/platforms');

    $response->assertForbidden();
});

test('super admin can create a platform', function () {
    $admin = User::factory()->create();
    $admin->assignRole('Super Admin');

    $response = $this->actingAs($admin)->post('/platforms', [
        'name' => 'SHOPEE',
        'is_active' => true,
    ]);

    $response
        ->assertRedirect('/platforms')
        ->assertSessionHas('success', 'Platform berhasil dibuat.');

    $this->assertDatabaseHas('platforms', [
        'name' => 'SHOPEE',
        'is_active' => true,
    ]);
});

test('super admin can update a platform', function () {
    $admin = User::factory()->create();
    $admin->assignRole('Super Admin');

    $platform = Platform::create([
        'name' => 'SHOPEE',
        'is_active' => true,
    ]);

    $response = $this->actingAs($admin)->put("/platforms/{$platform->id}", [
        'name' => 'SHOPEE MALL',
        'is_active' => false,
    ]);

    $response
        ->assertRedirect('/platforms')
        ->assertSessionHas('success', 'Platform berhasil diperbarui.');

    $this->assertDatabaseHas('platforms', [
        'id' => $platform->id,
        'name' => 'SHOPEE MALL',
        'is_active' => false,
    ]);
});

test('super admin can delete a platform', function () {
    $admin = User::factory()->create();
    $admin->assignRole('Super Admin');

    $platform = Platform::create([
        'name' => 'SHOPEE',
        'is_active' => true,
    ]);

    $response = $this->actingAs($admin)->delete("/platforms/{$platform->id}");

    $response
        ->assertRedirect('/platforms')
        ->assertSessionHas('success', 'Platform berhasil dihapus.');

    $this->assertDatabaseMissing('platforms', [
        'id' => $platform->id,
    ]);
});
