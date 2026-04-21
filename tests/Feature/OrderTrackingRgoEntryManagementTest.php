<?php

use App\Models\OrderTrackingRgoEntry;
use App\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Permission::findOrCreate('view order tracking rgo entries');
    Permission::findOrCreate('create order tracking rgo entries');
    Permission::findOrCreate('update order tracking rgo entries');
    Permission::findOrCreate('delete order tracking rgo entries');

    Role::findOrCreate('Super Admin')->syncPermissions([
        'view order tracking rgo entries',
        'create order tracking rgo entries',
        'update order tracking rgo entries',
        'delete order tracking rgo entries',
    ]);

    Role::findOrCreate('CS');
});

test('super admin can visit the order tracking rgo entry management page', function () {
    $admin = User::factory()->create();
    $admin->assignRole('Super Admin');

    $response = $this->actingAs($admin)->get('/order-tracking-rgo-entries');

    $response->assertOk();
});

test('non admin users cannot visit the order tracking rgo entry management page', function () {
    $user = User::factory()->create();
    $user->assignRole('CS');

    $response = $this->actingAs($user)->get('/order-tracking-rgo-entries');

    $response->assertForbidden();
});

test('super admin can create an order tracking rgo entry', function () {
    $admin = User::factory()->create();
    $admin->assignRole('Super Admin');

    $response = $this->actingAs($admin)->post('/order-tracking-rgo-entries', [
        'order_id' => 'OT-RGO-1001',
        'notes' => 'Imported from spreadsheet',
        'is_active' => true,
    ]);

    $response->assertRedirect('/order-tracking-rgo-entries')->assertSessionHas('success', 'Data RGO order tracking berhasil dibuat.');

    $this->assertDatabaseHas('order_tracking_rgo_entries', [
        'order_id' => 'OT-RGO-1001',
        'notes' => 'Imported from spreadsheet',
        'is_active' => true,
    ]);
});

test('super admin can update an order tracking rgo entry', function () {
    $admin = User::factory()->create();
    $admin->assignRole('Super Admin');

    $entry = OrderTrackingRgoEntry::create([
        'order_id' => 'OT-RGO-1001',
        'notes' => 'Old note',
        'is_active' => true,
    ]);

    $response = $this->actingAs($admin)->put("/order-tracking-rgo-entries/{$entry->id}", [
        'order_id' => 'OT-RGO-1002',
        'notes' => 'Updated note',
        'is_active' => false,
    ]);

    $response->assertRedirect('/order-tracking-rgo-entries')->assertSessionHas('success', 'Data RGO order tracking berhasil diperbarui.');

    $this->assertDatabaseHas('order_tracking_rgo_entries', [
        'id' => $entry->id,
        'order_id' => 'OT-RGO-1002',
        'notes' => 'Updated note',
        'is_active' => false,
    ]);
});

test('super admin can delete an order tracking rgo entry', function () {
    $admin = User::factory()->create();
    $admin->assignRole('Super Admin');

    $entry = OrderTrackingRgoEntry::create([
        'order_id' => 'OT-RGO-1001',
        'notes' => 'Delete me',
        'is_active' => true,
    ]);

    $response = $this->actingAs($admin)->delete("/order-tracking-rgo-entries/{$entry->id}");

    $response->assertRedirect('/order-tracking-rgo-entries')->assertSessionHas('success', 'Data RGO order tracking berhasil dihapus.');

    $this->assertDatabaseMissing('order_tracking_rgo_entries', [
        'id' => $entry->id,
    ]);
});
