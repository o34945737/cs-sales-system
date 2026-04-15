<?php

use App\Models\PartOfBad;
use App\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Permission::findOrCreate('view part of bads');
    Permission::findOrCreate('create part of bads');
    Permission::findOrCreate('update part of bads');
    Permission::findOrCreate('delete part of bads');

    Role::findOrCreate('Super Admin')->syncPermissions([
        'view part of bads',
        'create part of bads',
        'update part of bads',
        'delete part of bads',
    ]);

    Role::findOrCreate('CS');
});

test('super admin can visit the part of bad management page', function () {
    $admin = User::factory()->create();
    $admin->assignRole('Super Admin');

    $response = $this->actingAs($admin)->get('/part-of-bads');

    $response->assertOk();
});

test('non admin users cannot visit the part of bad management page', function () {
    $user = User::factory()->create();
    $user->assignRole('CS');

    $response = $this->actingAs($user)->get('/part-of-bads');

    $response->assertForbidden();
});

test('super admin can create a part of bad', function () {
    $admin = User::factory()->create();
    $admin->assignRole('Super Admin');

    $response = $this->actingAs($admin)->post('/part-of-bads', [
        'name' => 'Packaging',
        'is_active' => true,
    ]);

    $response->assertRedirect('/part-of-bads')->assertSessionHas('success', 'Part of bad berhasil dibuat.');

    $this->assertDatabaseHas('part_of_bads', [
        'name' => 'Packaging',
        'is_active' => true,
    ]);
});

test('super admin can update a part of bad', function () {
    $admin = User::factory()->create();
    $admin->assignRole('Super Admin');

    $partOfBad = PartOfBad::create([
        'name' => 'Packaging',
        'is_active' => true,
    ]);

    $response = $this->actingAs($admin)->put("/part-of-bads/{$partOfBad->id}", [
        'name' => 'Upper',
        'is_active' => false,
    ]);

    $response->assertRedirect('/part-of-bads')->assertSessionHas('success', 'Part of bad berhasil diperbarui.');

    $this->assertDatabaseHas('part_of_bads', [
        'id' => $partOfBad->id,
        'name' => 'Upper',
        'is_active' => false,
    ]);
});

test('super admin can delete a part of bad', function () {
    $admin = User::factory()->create();
    $admin->assignRole('Super Admin');

    $partOfBad = PartOfBad::create([
        'name' => 'Packaging',
        'is_active' => true,
    ]);

    $response = $this->actingAs($admin)->delete("/part-of-bads/{$partOfBad->id}");

    $response->assertRedirect('/part-of-bads')->assertSessionHas('success', 'Part of bad berhasil dihapus.');

    $this->assertDatabaseMissing('part_of_bads', [
        'id' => $partOfBad->id,
    ]);
});
