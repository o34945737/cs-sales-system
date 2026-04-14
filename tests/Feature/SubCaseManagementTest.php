<?php

use App\Models\SubCase;
use App\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Permission::findOrCreate('view sub cases');
    Permission::findOrCreate('create sub cases');
    Permission::findOrCreate('update sub cases');
    Permission::findOrCreate('delete sub cases');

    Role::findOrCreate('Super Admin')->syncPermissions([
        'view sub cases',
        'create sub cases',
        'update sub cases',
        'delete sub cases',
    ]);

    Role::findOrCreate('CS');
});

test('super admin can visit the sub case management page', function () {
    $admin = User::factory()->create();
    $admin->assignRole('Super Admin');

    $response = $this->actingAs($admin)->get('/sub-cases');

    $response->assertOk();
});

test('non admin users cannot visit the sub case management page', function () {
    $user = User::factory()->create();
    $user->assignRole('CS');

    $response = $this->actingAs($user)->get('/sub-cases');

    $response->assertForbidden();
});

test('super admin can create a sub case', function () {
    $admin = User::factory()->create();
    $admin->assignRole('Super Admin');

    $response = $this->actingAs($admin)->post('/sub-cases', [
        'name' => 'Bad Quality Product',
        'default_cause_by' => 'BRAND',
        'is_active' => true,
    ]);

    $response
        ->assertRedirect('/sub-cases')
        ->assertSessionHas('success', 'Sub case berhasil dibuat.');

    $this->assertDatabaseHas('sub_cases', [
        'name' => 'Bad Quality Product',
        'default_cause_by' => 'BRAND',
        'is_active' => true,
    ]);
});

test('super admin can update a sub case', function () {
    $admin = User::factory()->create();
    $admin->assignRole('Super Admin');

    $subCase = SubCase::create([
        'name' => 'Bad Quality Product',
        'default_cause_by' => 'BRAND',
        'is_active' => true,
    ]);

    $response = $this->actingAs($admin)->put("/sub-cases/{$subCase->id}", [
        'name' => 'Damaged Product',
        'default_cause_by' => null,
        'is_active' => false,
    ]);

    $response
        ->assertRedirect('/sub-cases')
        ->assertSessionHas('success', 'Sub case berhasil diperbarui.');

    $this->assertDatabaseHas('sub_cases', [
        'id' => $subCase->id,
        'name' => 'Damaged Product',
        'default_cause_by' => null,
        'is_active' => false,
    ]);
});

test('super admin can delete a sub case', function () {
    $admin = User::factory()->create();
    $admin->assignRole('Super Admin');

    $subCase = SubCase::create([
        'name' => 'Bad Quality Product',
        'default_cause_by' => 'BRAND',
        'is_active' => true,
    ]);

    $response = $this->actingAs($admin)->delete("/sub-cases/{$subCase->id}");

    $response
        ->assertRedirect('/sub-cases')
        ->assertSessionHas('success', 'Sub case berhasil dihapus.');

    $this->assertDatabaseMissing('sub_cases', [
        'id' => $subCase->id,
    ]);
});
