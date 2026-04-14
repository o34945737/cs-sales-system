<?php

use App\Models\OosReason;
use App\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Permission::findOrCreate('view oos reasons');
    Permission::findOrCreate('create oos reasons');
    Permission::findOrCreate('update oos reasons');
    Permission::findOrCreate('delete oos reasons');

    Role::findOrCreate('Super Admin')->syncPermissions([
        'view oos reasons',
        'create oos reasons',
        'update oos reasons',
        'delete oos reasons',
    ]);

    Role::findOrCreate('CS');
});

test('super admin can visit the oos reason management page', function () {
    $admin = User::factory()->create();
    $admin->assignRole('Super Admin');

    $response = $this->actingAs($admin)->get('/oos-reasons');

    $response->assertOk();
});

test('non admin users cannot visit the oos reason management page', function () {
    $user = User::factory()->create();
    $user->assignRole('CS');

    $response = $this->actingAs($user)->get('/oos-reasons');

    $response->assertForbidden();
});

test('super admin can create an oos reason', function () {
    $admin = User::factory()->create();
    $admin->assignRole('Super Admin');

    $response = $this->actingAs($admin)->post('/oos-reasons', [
        'name' => 'Inventory mismatch',
        'is_active' => true,
    ]);

    $response->assertRedirect('/oos-reasons')->assertSessionHas('success', 'OOS reason berhasil dibuat.');

    $this->assertDatabaseHas('oos_reasons', [
        'name' => 'Inventory mismatch',
        'is_active' => true,
    ]);
});

test('super admin can update an oos reason', function () {
    $admin = User::factory()->create();
    $admin->assignRole('Super Admin');

    $reason = OosReason::create([
        'name' => 'Inventory mismatch',
        'is_active' => true,
    ]);

    $response = $this->actingAs($admin)->put("/oos-reasons/{$reason->id}", [
        'name' => 'Stock kosong di gudang',
        'is_active' => false,
    ]);

    $response->assertRedirect('/oos-reasons')->assertSessionHas('success', 'OOS reason berhasil diperbarui.');

    $this->assertDatabaseHas('oos_reasons', [
        'id' => $reason->id,
        'name' => 'Stock kosong di gudang',
        'is_active' => false,
    ]);
});

test('super admin can delete an oos reason', function () {
    $admin = User::factory()->create();
    $admin->assignRole('Super Admin');

    $reason = OosReason::create([
        'name' => 'Inventory mismatch',
        'is_active' => true,
    ]);

    $response = $this->actingAs($admin)->delete("/oos-reasons/{$reason->id}");

    $response->assertRedirect('/oos-reasons')->assertSessionHas('success', 'OOS reason berhasil dihapus.');

    $this->assertDatabaseMissing('oos_reasons', [
        'id' => $reason->id,
    ]);
});
