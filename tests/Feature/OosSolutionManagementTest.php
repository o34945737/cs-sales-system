<?php

use App\Models\OosSolution;
use App\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Permission::findOrCreate('view oos solutions');
    Permission::findOrCreate('create oos solutions');
    Permission::findOrCreate('update oos solutions');
    Permission::findOrCreate('delete oos solutions');

    Role::findOrCreate('Super Admin')->syncPermissions([
        'view oos solutions',
        'create oos solutions',
        'update oos solutions',
        'delete oos solutions',
    ]);

    Role::findOrCreate('CS');
});

test('super admin can visit the oos solution management page', function () {
    $admin = User::factory()->create();
    $admin->assignRole('Super Admin');

    $response = $this->actingAs($admin)->get('/oos-solutions');

    $response->assertOk();
});

test('non admin users cannot visit the oos solution management page', function () {
    $user = User::factory()->create();
    $user->assignRole('CS');

    $response = $this->actingAs($user)->get('/oos-solutions');

    $response->assertForbidden();
});

test('super admin can create an oos solution', function () {
    $admin = User::factory()->create();
    $admin->assignRole('Super Admin');

    $response = $this->actingAs($admin)->post('/oos-solutions', [
        'name' => 'Offer refund',
        'is_active' => true,
    ]);

    $response->assertRedirect('/oos-solutions')->assertSessionHas('success', 'OOS solution berhasil dibuat.');

    $this->assertDatabaseHas('oos_solutions', [
        'name' => 'Offer refund',
        'is_active' => true,
    ]);
});

test('super admin can update an oos solution', function () {
    $admin = User::factory()->create();
    $admin->assignRole('Super Admin');

    $solution = OosSolution::create([
        'name' => 'Offer refund',
        'is_active' => true,
    ]);

    $response = $this->actingAs($admin)->put("/oos-solutions/{$solution->id}", [
        'name' => 'Wait restock confirmation',
        'is_active' => false,
    ]);

    $response->assertRedirect('/oos-solutions')->assertSessionHas('success', 'OOS solution berhasil diperbarui.');

    $this->assertDatabaseHas('oos_solutions', [
        'id' => $solution->id,
        'name' => 'Wait restock confirmation',
        'is_active' => false,
    ]);
});

test('super admin can delete an oos solution', function () {
    $admin = User::factory()->create();
    $admin->assignRole('Super Admin');

    $solution = OosSolution::create([
        'name' => 'Offer refund',
        'is_active' => true,
    ]);

    $response = $this->actingAs($admin)->delete("/oos-solutions/{$solution->id}");

    $response->assertRedirect('/oos-solutions')->assertSessionHas('success', 'OOS solution berhasil dihapus.');

    $this->assertDatabaseMissing('oos_solutions', [
        'id' => $solution->id,
    ]);
});
