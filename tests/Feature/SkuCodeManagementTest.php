<?php

use App\Models\SkuCode;
use App\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Permission::findOrCreate('view sku codes');
    Permission::findOrCreate('create sku codes');
    Permission::findOrCreate('update sku codes');
    Permission::findOrCreate('delete sku codes');

    Role::findOrCreate('Super Admin')->syncPermissions([
        'view sku codes',
        'create sku codes',
        'update sku codes',
        'delete sku codes',
    ]);

    Role::findOrCreate('CS');
});

test('super admin can visit the sku code management page', function () {
    $admin = User::factory()->create();
    $admin->assignRole('Super Admin');

    $response = $this->actingAs($admin)->get('/sku-codes');

    $response->assertOk();
});

test('non admin users cannot visit the sku code management page', function () {
    $user = User::factory()->create();
    $user->assignRole('CS');

    $response = $this->actingAs($user)->get('/sku-codes');

    $response->assertForbidden();
});

test('super admin can create a sku code', function () {
    $admin = User::factory()->create();
    $admin->assignRole('Super Admin');

    $response = $this->actingAs($admin)->post('/sku-codes', [
        'sku' => 'SKU-1001',
        'product_name' => 'Sepatu Running',
    ]);

    $response
        ->assertRedirect('/sku-codes')
        ->assertSessionHas('success', 'SKU Code berhasil dibuat.');

    $this->assertDatabaseHas('sku_codes', [
        'sku' => 'SKU-1001',
        'product_name' => 'Sepatu Running',
    ]);
});

test('super admin can update a sku code', function () {
    $admin = User::factory()->create();
    $admin->assignRole('Super Admin');

    $skuCode = SkuCode::create([
        'sku' => 'SKU-1001',
        'product_name' => 'Sepatu Running',
    ]);

    $response = $this->actingAs($admin)->put("/sku-codes/{$skuCode->id}", [
        'sku' => 'SKU-1001-A',
        'product_name' => 'Sepatu Running Pro',
    ]);

    $response
        ->assertRedirect('/sku-codes')
        ->assertSessionHas('success', 'SKU Code berhasil diperbarui.');

    $this->assertDatabaseHas('sku_codes', [
        'id' => $skuCode->id,
        'sku' => 'SKU-1001-A',
        'product_name' => 'Sepatu Running Pro',
    ]);
});

test('super admin can delete a sku code', function () {
    $admin = User::factory()->create();
    $admin->assignRole('Super Admin');

    $skuCode = SkuCode::create([
        'sku' => 'SKU-1001',
        'product_name' => 'Sepatu Running',
    ]);

    $response = $this->actingAs($admin)->delete("/sku-codes/{$skuCode->id}");

    $response
        ->assertRedirect('/sku-codes')
        ->assertSessionHas('success', 'SKU Code berhasil dihapus.');

    $this->assertDatabaseMissing('sku_codes', [
        'id' => $skuCode->id,
    ]);
});
