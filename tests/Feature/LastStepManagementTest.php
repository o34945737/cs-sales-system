<?php

use App\Models\LastStep;
use App\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Permission::findOrCreate('view last steps');
    Permission::findOrCreate('create last steps');
    Permission::findOrCreate('update last steps');
    Permission::findOrCreate('delete last steps');

    Role::findOrCreate('Super Admin')->syncPermissions([
        'view last steps',
        'create last steps',
        'update last steps',
        'delete last steps',
    ]);

    Role::findOrCreate('CS');
});

test('super admin can visit the last step management page', function () {
    $admin = User::factory()->create();
    $admin->assignRole('Super Admin');

    $response = $this->actingAs($admin)->get('/last-steps');

    $response->assertOk();
});

test('non admin users cannot visit the last step management page', function () {
    $user = User::factory()->create();
    $user->assignRole('CS');

    $response = $this->actingAs($user)->get('/last-steps');

    $response->assertForbidden();
});

test('super admin can create a last step', function () {
    $admin = User::factory()->create();
    $admin->assignRole('Super Admin');

    $response = $this->actingAs($admin)->post('/last-steps', [
        'name' => 'Follow Up WH',
        'status_result' => 'Pending',
        'priority_level' => 'P1',
        'is_active' => true,
    ]);

    $response->assertRedirect('/last-steps')->assertSessionHas('success', 'Last step berhasil dibuat.');

    $this->assertDatabaseHas('last_steps', [
        'name' => 'Follow Up WH',
        'status_result' => 'Pending',
        'priority_level' => 'P1',
        'is_active' => true,
    ]);
});

test('super admin can update a last step', function () {
    $admin = User::factory()->create();
    $admin->assignRole('Super Admin');

    $lastStep = LastStep::create([
        'name' => 'Follow Up WH',
        'status_result' => 'Pending',
        'priority_level' => 'P1',
        'is_active' => true,
    ]);

    $response = $this->actingAs($admin)->put("/last-steps/{$lastStep->id}", [
        'name' => 'Seller Win',
        'status_result' => 'Solved',
        'priority_level' => 'Cool',
        'is_active' => false,
    ]);

    $response->assertRedirect('/last-steps')->assertSessionHas('success', 'Last step berhasil diperbarui.');

    $this->assertDatabaseHas('last_steps', [
        'id' => $lastStep->id,
        'name' => 'Seller Win',
        'status_result' => 'Solved',
        'priority_level' => 'Cool',
        'is_active' => false,
    ]);
});

test('super admin can delete a last step', function () {
    $admin = User::factory()->create();
    $admin->assignRole('Super Admin');

    $lastStep = LastStep::create([
        'name' => 'Follow Up WH',
        'status_result' => 'Pending',
        'priority_level' => 'P1',
        'is_active' => true,
    ]);

    $response = $this->actingAs($admin)->delete("/last-steps/{$lastStep->id}");

    $response->assertRedirect('/last-steps')->assertSessionHas('success', 'Last step berhasil dihapus.');

    $this->assertDatabaseMissing('last_steps', [
        'id' => $lastStep->id,
    ]);
});
