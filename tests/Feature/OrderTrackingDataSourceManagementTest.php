<?php

use App\Models\OrderTrackingDataSource;
use App\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Permission::findOrCreate('view order tracking data sources');
    Permission::findOrCreate('create order tracking data sources');
    Permission::findOrCreate('update order tracking data sources');
    Permission::findOrCreate('delete order tracking data sources');

    Role::findOrCreate('Super Admin')->syncPermissions([
        'view order tracking data sources',
        'create order tracking data sources',
        'update order tracking data sources',
        'delete order tracking data sources',
    ]);

    Role::findOrCreate('CS');
});

test('super admin can visit the order tracking data source management page', function () {
    $admin = User::factory()->create();
    $admin->assignRole('Super Admin');

    $response = $this->actingAs($admin)->get('/order-tracking-data-sources');

    $response->assertOk();
});

test('non admin users cannot visit the order tracking data source management page', function () {
    $user = User::factory()->create();
    $user->assignRole('CS');

    $response = $this->actingAs($user)->get('/order-tracking-data-sources');

    $response->assertForbidden();
});

test('super admin can create an order tracking data source', function () {
    $admin = User::factory()->create();
    $admin->assignRole('Super Admin');

    $response = $this->actingAs($admin)->post('/order-tracking-data-sources', [
        'name' => 'WH',
        'is_active' => true,
    ]);

    $response->assertRedirect('/order-tracking-data-sources')->assertSessionHas('success', 'Data source order tracking berhasil dibuat.');

    $this->assertDatabaseHas('order_tracking_data_sources', [
        'name' => 'WH',
        'is_active' => true,
    ]);
});

test('super admin can update an order tracking data source', function () {
    $admin = User::factory()->create();
    $admin->assignRole('Super Admin');

    $dataSource = OrderTrackingDataSource::create([
        'name' => 'WH',
        'is_active' => true,
    ]);

    $response = $this->actingAs($admin)->put("/order-tracking-data-sources/{$dataSource->id}", [
        'name' => 'Finance',
        'is_active' => false,
    ]);

    $response->assertRedirect('/order-tracking-data-sources')->assertSessionHas('success', 'Data source order tracking berhasil diperbarui.');

    $this->assertDatabaseHas('order_tracking_data_sources', [
        'id' => $dataSource->id,
        'name' => 'Finance',
        'is_active' => false,
    ]);
});

test('super admin can delete an order tracking data source', function () {
    $admin = User::factory()->create();
    $admin->assignRole('Super Admin');

    $dataSource = OrderTrackingDataSource::create([
        'name' => 'WH',
        'is_active' => true,
    ]);

    $response = $this->actingAs($admin)->delete("/order-tracking-data-sources/{$dataSource->id}");

    $response->assertRedirect('/order-tracking-data-sources')->assertSessionHas('success', 'Data source order tracking berhasil dihapus.');

    $this->assertDatabaseMissing('order_tracking_data_sources', [
        'id' => $dataSource->id,
    ]);
});
