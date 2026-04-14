<?php

use App\Models\CauseBy;
use App\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Permission::findOrCreate('view cause bys');
    Permission::findOrCreate('create cause bys');
    Permission::findOrCreate('update cause bys');
    Permission::findOrCreate('delete cause bys');

    Role::findOrCreate('Super Admin')->syncPermissions([
        'view cause bys',
        'create cause bys',
        'update cause bys',
        'delete cause bys',
    ]);

    Role::findOrCreate('CS');
});

test('super admin can visit the cause by management page', function () {
    $admin = User::factory()->create();
    $admin->assignRole('Super Admin');

    $response = $this->actingAs($admin)->get('/cause-bys');

    $response->assertOk();
});

test('non admin users cannot visit the cause by management page', function () {
    $user = User::factory()->create();
    $user->assignRole('CS');

    $response = $this->actingAs($user)->get('/cause-bys');

    $response->assertForbidden();
});

test('super admin can create a cause by', function () {
    $admin = User::factory()->create();
    $admin->assignRole('Super Admin');

    $response = $this->actingAs($admin)->post('/cause-bys', [
        'name' => 'BRAND',
        'is_active' => true,
    ]);

    $response->assertRedirect('/cause-bys')->assertSessionHas('success', 'Cause by berhasil dibuat.');

    $this->assertDatabaseHas('cause_bys', [
        'name' => 'BRAND',
        'is_active' => true,
    ]);
});

test('super admin can update a cause by', function () {
    $admin = User::factory()->create();
    $admin->assignRole('Super Admin');

    $causeBy = CauseBy::create([
        'name' => 'BRAND',
        'is_active' => true,
    ]);

    $response = $this->actingAs($admin)->put("/cause-bys/{$causeBy->id}", [
        'name' => 'CUSTOMER',
        'is_active' => false,
    ]);

    $response->assertRedirect('/cause-bys')->assertSessionHas('success', 'Cause by berhasil diperbarui.');

    $this->assertDatabaseHas('cause_bys', [
        'id' => $causeBy->id,
        'name' => 'CUSTOMER',
        'is_active' => false,
    ]);
});

test('super admin can delete a cause by', function () {
    $admin = User::factory()->create();
    $admin->assignRole('Super Admin');

    $causeBy = CauseBy::create([
        'name' => 'BRAND',
        'is_active' => true,
    ]);

    $response = $this->actingAs($admin)->delete("/cause-bys/{$causeBy->id}");

    $response->assertRedirect('/cause-bys')->assertSessionHas('success', 'Cause by berhasil dihapus.');

    $this->assertDatabaseMissing('cause_bys', [
        'id' => $causeBy->id,
    ]);
});
