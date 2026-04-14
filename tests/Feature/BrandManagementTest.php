<?php

use App\Models\Brand;
use App\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Permission::findOrCreate('view brands');
    Permission::findOrCreate('create brands');
    Permission::findOrCreate('update brands');
    Permission::findOrCreate('delete brands');

    Role::findOrCreate('Super Admin')->syncPermissions([
        'view brands',
        'create brands',
        'update brands',
        'delete brands',
    ]);

    Role::findOrCreate('CS');
});

test('super admin can visit the brand management page', function () {
    $admin = User::factory()->create();
    $admin->assignRole('Super Admin');

    $response = $this->actingAs($admin)->get('/brands');

    $response->assertOk();
});

test('non admin users cannot visit the brand management page', function () {
    $user = User::factory()->create();
    $user->assignRole('CS');

    $response = $this->actingAs($user)->get('/brands');

    $response->assertForbidden();
});

test('super admin can create a brand', function () {
    $admin = User::factory()->create();
    $admin->assignRole('Super Admin');

    $response = $this->actingAs($admin)->post('/brands', [
        'name' => 'ANTA',
        'is_active' => true,
    ]);

    $response
        ->assertRedirect('/brands')
        ->assertSessionHas('success', 'Brand berhasil dibuat.');

    $this->assertDatabaseHas('brands', [
        'name' => 'ANTA',
        'is_active' => true,
    ]);
});

test('super admin can update a brand', function () {
    $admin = User::factory()->create();
    $admin->assignRole('Super Admin');

    $brand = Brand::create([
        'name' => 'ANTA',
        'is_active' => true,
    ]);

    $response = $this->actingAs($admin)->put("/brands/{$brand->id}", [
        'name' => 'ANTA Official',
        'is_active' => false,
    ]);

    $response
        ->assertRedirect('/brands')
        ->assertSessionHas('success', 'Brand berhasil diperbarui.');

    $this->assertDatabaseHas('brands', [
        'id' => $brand->id,
        'name' => 'ANTA Official',
        'is_active' => false,
    ]);
});

test('super admin can delete a brand', function () {
    $admin = User::factory()->create();
    $admin->assignRole('Super Admin');

    $brand = Brand::create([
        'name' => 'ANTA',
        'is_active' => true,
    ]);

    $response = $this->actingAs($admin)->delete("/brands/{$brand->id}");

    $response
        ->assertRedirect('/brands')
        ->assertSessionHas('success', 'Brand berhasil dihapus.');

    $this->assertDatabaseMissing('brands', [
        'id' => $brand->id,
    ]);
});
