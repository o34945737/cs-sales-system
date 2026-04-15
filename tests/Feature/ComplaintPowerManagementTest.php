<?php

use App\Models\ComplaintPower;
use App\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Permission::findOrCreate('view complaint powers');
    Permission::findOrCreate('create complaint powers');
    Permission::findOrCreate('update complaint powers');
    Permission::findOrCreate('delete complaint powers');

    Role::findOrCreate('Super Admin')->syncPermissions([
        'view complaint powers',
        'create complaint powers',
        'update complaint powers',
        'delete complaint powers',
    ]);

    Role::findOrCreate('CS');
});

test('super admin can visit the complaint power management page', function () {
    $admin = User::factory()->create();
    $admin->assignRole('Super Admin');

    $response = $this->actingAs($admin)->get('/complaint-powers');

    $response->assertOk();
});

test('non admin users cannot visit the complaint power management page', function () {
    $user = User::factory()->create();
    $user->assignRole('CS');

    $response = $this->actingAs($user)->get('/complaint-powers');

    $response->assertForbidden();
});

test('super admin can create a complaint power', function () {
    $admin = User::factory()->create();
    $admin->assignRole('Super Admin');

    $response = $this->actingAs($admin)->post('/complaint-powers', [
        'name' => 'Hard Complaint',
        'is_active' => true,
    ]);

    $response->assertRedirect('/complaint-powers')->assertSessionHas('success', 'Complaint power berhasil dibuat.');

    $this->assertDatabaseHas('complaint_powers', [
        'name' => 'Hard Complaint',
        'is_active' => true,
    ]);
});

test('super admin can update a complaint power', function () {
    $admin = User::factory()->create();
    $admin->assignRole('Super Admin');

    $complaintPower = ComplaintPower::create([
        'name' => 'Hard Complaint',
        'is_active' => true,
    ]);

    $response = $this->actingAs($admin)->put("/complaint-powers/{$complaintPower->id}", [
        'name' => 'Normal Complaint',
        'is_active' => false,
    ]);

    $response->assertRedirect('/complaint-powers')->assertSessionHas('success', 'Complaint power berhasil diperbarui.');

    $this->assertDatabaseHas('complaint_powers', [
        'id' => $complaintPower->id,
        'name' => 'Normal Complaint',
        'is_active' => false,
    ]);
});

test('super admin can delete a complaint power', function () {
    $admin = User::factory()->create();
    $admin->assignRole('Super Admin');

    $complaintPower = ComplaintPower::create([
        'name' => 'Hard Complaint',
        'is_active' => true,
    ]);

    $response = $this->actingAs($admin)->delete("/complaint-powers/{$complaintPower->id}");

    $response->assertRedirect('/complaint-powers')->assertSessionHas('success', 'Complaint power berhasil dihapus.');

    $this->assertDatabaseMissing('complaint_powers', [
        'id' => $complaintPower->id,
    ]);
});
