<?php

use App\Models\ComplaintStepStatus;
use App\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Permission::findOrCreate('view complaint step statuses');
    Permission::findOrCreate('create complaint step statuses');
    Permission::findOrCreate('update complaint step statuses');
    Permission::findOrCreate('delete complaint step statuses');

    Role::findOrCreate('Super Admin')->syncPermissions([
        'view complaint step statuses',
        'create complaint step statuses',
        'update complaint step statuses',
        'delete complaint step statuses',
    ]);

    Role::findOrCreate('CS');
});

test('super admin can visit the complaint step status management page', function () {
    $admin = User::factory()->create();
    $admin->assignRole('Super Admin');

    $response = $this->actingAs($admin)->get('/complaint-step-statuses');

    $response->assertOk();
});

test('non admin users cannot visit the complaint step status management page', function () {
    $user = User::factory()->create();
    $user->assignRole('CS');

    $response = $this->actingAs($user)->get('/complaint-step-statuses');

    $response->assertForbidden();
});

test('super admin can create a complaint step status', function () {
    $admin = User::factory()->create();
    $admin->assignRole('Super Admin');

    $response = $this->actingAs($admin)->post('/complaint-step-statuses', [
        'name' => 'YES',
        'is_active' => true,
    ]);

    $response->assertRedirect('/complaint-step-statuses')->assertSessionHas('success', 'Complaint step status berhasil dibuat.');

    $this->assertDatabaseHas('complaint_step_statuses', [
        'name' => 'YES',
        'is_active' => true,
    ]);
});

test('super admin can update a complaint step status', function () {
    $admin = User::factory()->create();
    $admin->assignRole('Super Admin');

    $complaintStepStatus = ComplaintStepStatus::create([
        'name' => 'YES',
        'is_active' => true,
    ]);

    $response = $this->actingAs($admin)->put("/complaint-step-statuses/{$complaintStepStatus->id}", [
        'name' => 'NO',
        'is_active' => false,
    ]);

    $response->assertRedirect('/complaint-step-statuses')->assertSessionHas('success', 'Complaint step status berhasil diperbarui.');

    $this->assertDatabaseHas('complaint_step_statuses', [
        'id' => $complaintStepStatus->id,
        'name' => 'NO',
        'is_active' => false,
    ]);
});

test('super admin can delete a complaint step status', function () {
    $admin = User::factory()->create();
    $admin->assignRole('Super Admin');

    $complaintStepStatus = ComplaintStepStatus::create([
        'name' => 'YES',
        'is_active' => true,
    ]);

    $response = $this->actingAs($admin)->delete("/complaint-step-statuses/{$complaintStepStatus->id}");

    $response->assertRedirect('/complaint-step-statuses')->assertSessionHas('success', 'Complaint step status berhasil dihapus.');

    $this->assertDatabaseMissing('complaint_step_statuses', [
        'id' => $complaintStepStatus->id,
    ]);
});
