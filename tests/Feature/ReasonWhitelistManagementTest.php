<?php

use App\Models\ReasonWhitelist;
use App\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Permission::findOrCreate('view reason whitelists');
    Permission::findOrCreate('create reason whitelists');
    Permission::findOrCreate('update reason whitelists');
    Permission::findOrCreate('delete reason whitelists');

    Role::findOrCreate('Super Admin')->syncPermissions([
        'view reason whitelists',
        'create reason whitelists',
        'update reason whitelists',
        'delete reason whitelists',
    ]);

    Role::findOrCreate('CS');
});

test('super admin can visit the reason whitelist management page', function () {
    $admin = User::factory()->create();
    $admin->assignRole('Super Admin');

    $response = $this->actingAs($admin)->get('/reason-whitelists');

    $response->assertOk();
});

test('non admin users cannot visit the reason whitelist management page', function () {
    $user = User::factory()->create();
    $user->assignRole('CS');

    $response = $this->actingAs($user)->get('/reason-whitelists');

    $response->assertForbidden();
});

test('super admin can create a reason whitelist', function () {
    $admin = User::factory()->create();
    $admin->assignRole('Super Admin');

    $response = $this->actingAs($admin)->post('/reason-whitelists', [
        'name' => 'Packing not proper',
        'is_active' => true,
    ]);

    $response->assertRedirect('/reason-whitelists')->assertSessionHas('success', 'Reason whitelist berhasil dibuat.');

    $this->assertDatabaseHas('reason_whitelists', [
        'name' => 'Packing not proper',
        'is_active' => true,
    ]);
});

test('super admin can update a reason whitelist', function () {
    $admin = User::factory()->create();
    $admin->assignRole('Super Admin');

    $reason = ReasonWhitelist::create([
        'name' => 'Packing not proper',
        'is_active' => true,
    ]);

    $response = $this->actingAs($admin)->put("/reason-whitelists/{$reason->id}", [
        'name' => 'Late Respons',
        'is_active' => false,
    ]);

    $response->assertRedirect('/reason-whitelists')->assertSessionHas('success', 'Reason whitelist berhasil diperbarui.');

    $this->assertDatabaseHas('reason_whitelists', [
        'id' => $reason->id,
        'name' => 'Late Respons',
        'is_active' => false,
    ]);
});

test('super admin can delete a reason whitelist', function () {
    $admin = User::factory()->create();
    $admin->assignRole('Super Admin');

    $reason = ReasonWhitelist::create([
        'name' => 'Packing not proper',
        'is_active' => true,
    ]);

    $response = $this->actingAs($admin)->delete("/reason-whitelists/{$reason->id}");

    $response->assertRedirect('/reason-whitelists')->assertSessionHas('success', 'Reason whitelist berhasil dihapus.');

    $this->assertDatabaseMissing('reason_whitelists', [
        'id' => $reason->id,
    ]);
});
