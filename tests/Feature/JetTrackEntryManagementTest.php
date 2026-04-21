<?php

use App\Models\JetTrackEntry;
use App\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Permission::findOrCreate('view jet track entries');
    Permission::findOrCreate('create jet track entries');
    Permission::findOrCreate('update jet track entries');
    Permission::findOrCreate('delete jet track entries');

    Role::findOrCreate('Super Admin')->syncPermissions([
        'view jet track entries',
        'create jet track entries',
        'update jet track entries',
        'delete jet track entries',
    ]);

    Role::findOrCreate('CS');
});

test('super admin can visit the jet track management page', function () {
    $admin = User::factory()->create();
    $admin->assignRole('Super Admin');

    $response = $this->actingAs($admin)->get('/jet-track-entries');

    $response->assertOk();
});

test('non admin users cannot visit the jet track management page', function () {
    $user = User::factory()->create();
    $user->assignRole('CS');

    $response = $this->actingAs($user)->get('/jet-track-entries');

    $response->assertForbidden();
});

test('super admin can create a jet track entry', function () {
    $admin = User::factory()->create();
    $admin->assignRole('Super Admin');

    $response = $this->actingAs($admin)->post('/jet-track-entries', [
        'awb' => 'JT-1001',
        'kondisi_barang' => 'Box penyok',
        'notes' => 'Imported from app',
        'is_active' => true,
    ]);

    $response->assertRedirect('/jet-track-entries')->assertSessionHas('success', 'Data Jet Track berhasil dibuat.');

    $this->assertDatabaseHas('jet_track_entries', [
        'awb' => 'JT-1001',
        'kondisi_barang' => 'Box penyok',
        'notes' => 'Imported from app',
        'is_active' => true,
    ]);
});

test('super admin can update a jet track entry', function () {
    $admin = User::factory()->create();
    $admin->assignRole('Super Admin');

    $entry = JetTrackEntry::create([
        'awb' => 'JT-1001',
        'kondisi_barang' => 'Box penyok',
        'notes' => 'Old note',
        'is_active' => true,
    ]);

    $response = $this->actingAs($admin)->put("/jet-track-entries/{$entry->id}", [
        'awb' => 'JT-1002',
        'kondisi_barang' => 'Inner rattle',
        'notes' => 'Updated note',
        'is_active' => false,
    ]);

    $response->assertRedirect('/jet-track-entries')->assertSessionHas('success', 'Data Jet Track berhasil diperbarui.');

    $this->assertDatabaseHas('jet_track_entries', [
        'id' => $entry->id,
        'awb' => 'JT-1002',
        'kondisi_barang' => 'Inner rattle',
        'notes' => 'Updated note',
        'is_active' => false,
    ]);
});

test('super admin can delete a jet track entry', function () {
    $admin = User::factory()->create();
    $admin->assignRole('Super Admin');

    $entry = JetTrackEntry::create([
        'awb' => 'JT-1001',
        'kondisi_barang' => 'Box penyok',
        'notes' => 'Delete me',
        'is_active' => true,
    ]);

    $response = $this->actingAs($admin)->delete("/jet-track-entries/{$entry->id}");

    $response->assertRedirect('/jet-track-entries')->assertSessionHas('success', 'Data Jet Track berhasil dihapus.');

    $this->assertDatabaseMissing('jet_track_entries', [
        'id' => $entry->id,
    ]);
});
