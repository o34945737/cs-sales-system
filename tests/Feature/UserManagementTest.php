<?php

use App\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Permission::findOrCreate('view users');
    Permission::findOrCreate('create users');
    Permission::findOrCreate('update users');
    Permission::findOrCreate('delete users');

    Role::findOrCreate('Super Admin')->syncPermissions([
        'view users',
        'create users',
        'update users',
        'delete users',
    ]);
    Role::findOrCreate('CS');
});

test('super admin can visit the user management page', function () {
    $admin = User::factory()->create();
    $admin->assignRole('Super Admin');

    $response = $this->actingAs($admin)->get('/users');

    $response->assertOk();
});

test('non admin users cannot visit the user management page', function () {
    $user = User::factory()->create();
    $user->assignRole('CS');

    $response = $this->actingAs($user)->get('/users');

    $response->assertForbidden();
});

test('super admin can create a user with a role and active flag', function () {
    $admin = User::factory()->create();
    $admin->assignRole('Super Admin');

    $response = $this->actingAs($admin)->post('/users', [
        'name' => 'Finance User',
        'email' => 'finance@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'role' => 'CS',
        'is_active' => true,
        'force_password_reset' => true,
    ]);

    $response->assertRedirect('/users');

    $createdUser = User::where('email', 'finance@example.com')->first();

    expect($createdUser)->not->toBeNull();
    expect($createdUser->is_active)->toBeTrue();
    expect($createdUser->force_password_reset)->toBeTrue();
    expect($createdUser->hasRole('CS'))->toBeTrue();
});

test('super admin can not deactivate their own account', function () {
    $admin = User::factory()->create([
        'is_active' => true,
    ]);
    $admin->assignRole('Super Admin');

    $response = $this->actingAs($admin)->put("/users/{$admin->id}", [
        'name' => $admin->name,
        'email' => $admin->email,
        'password' => '',
        'password_confirmation' => '',
        'role' => 'Super Admin',
        'is_active' => false,
        'force_password_reset' => false,
    ]);

    $response->assertSessionHasErrors('is_active');
});

test('super admin can not delete the last super admin account', function () {
    $admin = User::factory()->create([
        'is_active' => true,
    ]);
    $admin->assignRole('Super Admin');

    $otherAdmin = User::factory()->create([
        'is_active' => true,
    ]);
    $otherAdmin->assignRole('Super Admin');

    $response = $this->actingAs($admin)->delete("/users/{$otherAdmin->id}");

    $response->assertRedirect('/users');

    $followUp = $this->actingAs($admin)->delete("/users/{$admin->id}");

    $followUp->assertSessionHasErrors('delete');
});
