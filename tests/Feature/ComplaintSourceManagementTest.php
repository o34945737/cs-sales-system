<?php

use App\Models\ComplaintSource;
use App\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Permission::findOrCreate('view complaint sources');
    Permission::findOrCreate('create complaint sources');
    Permission::findOrCreate('update complaint sources');
    Permission::findOrCreate('delete complaint sources');

    Role::findOrCreate('Super Admin')->syncPermissions([
        'view complaint sources',
        'create complaint sources',
        'update complaint sources',
        'delete complaint sources',
    ]);

    Role::findOrCreate('CS');
});

test('super admin can visit the complaint source management page', function () {
    $admin = User::factory()->create();
    $admin->assignRole('Super Admin');

    $response = $this->actingAs($admin)->get('/complaint-sources');

    $response->assertOk();
});

test('non admin users cannot visit the complaint source management page', function () {
    $user = User::factory()->create();
    $user->assignRole('CS');

    $response = $this->actingAs($user)->get('/complaint-sources');

    $response->assertForbidden();
});

test('super admin can create a complaint source', function () {
    $admin = User::factory()->create();
    $admin->assignRole('Super Admin');

    $response = $this->actingAs($admin)->post('/complaint-sources', [
        'name' => 'After Sales',
        'is_active' => true,
    ]);

    $response->assertRedirect('/complaint-sources')->assertSessionHas('success', 'Complaint source berhasil dibuat.');

    $this->assertDatabaseHas('complaint_sources', [
        'name' => 'After Sales',
        'is_active' => true,
    ]);
});

test('super admin can update a complaint source', function () {
    $admin = User::factory()->create();
    $admin->assignRole('Super Admin');

    $complaintSource = ComplaintSource::create([
        'name' => 'After Sales',
        'is_active' => true,
    ]);

    $response = $this->actingAs($admin)->put("/complaint-sources/{$complaintSource->id}", [
        'name' => 'Brand',
        'is_active' => false,
    ]);

    $response->assertRedirect('/complaint-sources')->assertSessionHas('success', 'Complaint source berhasil diperbarui.');

    $this->assertDatabaseHas('complaint_sources', [
        'id' => $complaintSource->id,
        'name' => 'Brand',
        'is_active' => false,
    ]);
});

test('super admin can delete a complaint source', function () {
    $admin = User::factory()->create();
    $admin->assignRole('Super Admin');

    $complaintSource = ComplaintSource::create([
        'name' => 'After Sales',
        'is_active' => true,
    ]);

    $response = $this->actingAs($admin)->delete("/complaint-sources/{$complaintSource->id}");

    $response->assertRedirect('/complaint-sources')->assertSessionHas('success', 'Complaint source berhasil dihapus.');

    $this->assertDatabaseMissing('complaint_sources', [
        'id' => $complaintSource->id,
    ]);
});
