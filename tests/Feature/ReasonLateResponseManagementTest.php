<?php

use App\Models\ReasonLateResponse;
use App\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Permission::findOrCreate('view reason late responses');
    Permission::findOrCreate('create reason late responses');
    Permission::findOrCreate('update reason late responses');
    Permission::findOrCreate('delete reason late responses');

    Role::findOrCreate('Super Admin')->syncPermissions([
        'view reason late responses',
        'create reason late responses',
        'update reason late responses',
        'delete reason late responses',
    ]);

    Role::findOrCreate('CS');
});

test('super admin can visit the reason late response management page', function () {
    $admin = User::factory()->create();
    $admin->assignRole('Super Admin');

    $response = $this->actingAs($admin)->get('/reason-late-responses');

    $response->assertOk();
});

test('non admin users cannot visit the reason late response management page', function () {
    $user = User::factory()->create();
    $user->assignRole('CS');

    $response = $this->actingAs($user)->get('/reason-late-responses');

    $response->assertForbidden();
});

test('super admin can create a reason late response', function () {
    $admin = User::factory()->create();
    $admin->assignRole('Super Admin');

    $response = $this->actingAs($admin)->post('/reason-late-responses', [
        'name' => 'Need approval from warehouse',
        'is_active' => true,
    ]);

    $response->assertRedirect('/reason-late-responses')->assertSessionHas('success', 'Reason Late Response berhasil dibuat.');

    $this->assertDatabaseHas('reason_late_responses', [
        'name' => 'Need approval from warehouse',
        'is_active' => true,
    ]);
});

test('super admin can update a reason late response', function () {
    $admin = User::factory()->create();
    $admin->assignRole('Super Admin');

    $reason = ReasonLateResponse::create([
        'name' => 'Pending marketplace feedback',
        'is_active' => true,
    ]);

    $response = $this->actingAs($admin)->put("/reason-late-responses/{$reason->id}", [
        'name' => 'Pending courier confirmation',
        'is_active' => false,
    ]);

    $response->assertRedirect('/reason-late-responses')->assertSessionHas('success', 'Reason Late Response berhasil diperbarui.');

    $this->assertDatabaseHas('reason_late_responses', [
        'id' => $reason->id,
        'name' => 'Pending courier confirmation',
        'is_active' => false,
    ]);
});

test('super admin can delete a reason late response', function () {
    $admin = User::factory()->create();
    $admin->assignRole('Super Admin');

    $reason = ReasonLateResponse::create([
        'name' => 'Waiting customer response',
        'is_active' => true,
    ]);

    $response = $this->actingAs($admin)->delete("/reason-late-responses/{$reason->id}");

    $response->assertRedirect('/reason-late-responses')->assertSessionHas('success', 'Reason Late Response berhasil dihapus.');

    $this->assertDatabaseMissing('reason_late_responses', [
        'id' => $reason->id,
    ]);
});
