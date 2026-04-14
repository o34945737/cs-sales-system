<?php

use App\Models\Logistic;
use App\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Permission::findOrCreate('view logistics');
    Permission::findOrCreate('create logistics');
    Permission::findOrCreate('update logistics');
    Permission::findOrCreate('delete logistics');

    Role::findOrCreate('Super Admin')->syncPermissions([
        'view logistics',
        'create logistics',
        'update logistics',
        'delete logistics',
    ]);

    Role::findOrCreate('CS');
});

test('super admin can visit the logistic management page', function () {
    $admin = User::factory()->create();
    $admin->assignRole('Super Admin');

    $response = $this->actingAs($admin)->get('/logistics');

    $response->assertOk();
});

test('non admin users cannot visit the logistic management page', function () {
    $user = User::factory()->create();
    $user->assignRole('CS');

    $response = $this->actingAs($user)->get('/logistics');

    $response->assertForbidden();
});

test('super admin can create a logistic', function () {
    $admin = User::factory()->create();
    $admin->assignRole('Super Admin');

    $response = $this->actingAs($admin)->post('/logistics', [
        'name' => 'JNE',
        'is_active' => true,
    ]);

    $response
        ->assertRedirect('/logistics')
        ->assertSessionHas('success', 'Logistics berhasil dibuat.');

    $this->assertDatabaseHas('logistics', [
        'name' => 'JNE',
        'is_active' => true,
    ]);
});

test('super admin can update a logistic', function () {
    $admin = User::factory()->create();
    $admin->assignRole('Super Admin');

    $logistic = Logistic::create([
        'name' => 'JNE',
        'is_active' => true,
    ]);

    $response = $this->actingAs($admin)->put("/logistics/{$logistic->id}", [
        'name' => 'JNE Express',
        'is_active' => false,
    ]);

    $response
        ->assertRedirect('/logistics')
        ->assertSessionHas('success', 'Logistics berhasil diperbarui.');

    $this->assertDatabaseHas('logistics', [
        'id' => $logistic->id,
        'name' => 'JNE Express',
        'is_active' => false,
    ]);
});

test('super admin can delete a logistic', function () {
    $admin = User::factory()->create();
    $admin->assignRole('Super Admin');

    $logistic = Logistic::create([
        'name' => 'JNE',
        'is_active' => true,
    ]);

    $response = $this->actingAs($admin)->delete("/logistics/{$logistic->id}");

    $response
        ->assertRedirect('/logistics')
        ->assertSessionHas('success', 'Logistics berhasil dihapus.');

    $this->assertDatabaseMissing('logistics', [
        'id' => $logistic->id,
    ]);
});
