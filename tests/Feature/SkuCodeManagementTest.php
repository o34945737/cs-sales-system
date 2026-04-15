<?php

use App\Models\Brand;
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

    Brand::create([
        'name' => 'ANTA',
        'is_active' => true,
    ]);
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
        'brand' => 'ANTA',
        'default_value_of_product' => 550000,
        'is_active' => true,
    ]);

    $response
        ->assertRedirect('/sku-codes')
        ->assertSessionHas('success', 'SKU Code berhasil dibuat.');

    $this->assertDatabaseHas('sku_codes', [
        'sku' => 'SKU-1001',
        'product_name' => 'Sepatu Running',
        'brand' => 'ANTA',
        'is_active' => true,
    ]);
});

test('super admin can update a sku code', function () {
    $admin = User::factory()->create();
    $admin->assignRole('Super Admin');

    $skuCode = SkuCode::create([
        'sku' => 'SKU-1001',
        'product_name' => 'Sepatu Running',
        'brand' => 'ANTA',
        'default_value_of_product' => 550000,
        'is_active' => true,
    ]);

    $response = $this->actingAs($admin)->put("/sku-codes/{$skuCode->id}", [
        'sku' => 'SKU-1001-A',
        'product_name' => 'Sepatu Running Pro',
        'brand' => 'ANTA',
        'default_value_of_product' => 650000,
        'is_active' => false,
    ]);

    $response
        ->assertRedirect('/sku-codes')
        ->assertSessionHas('success', 'SKU Code berhasil diperbarui.');

    $this->assertDatabaseHas('sku_codes', [
        'id' => $skuCode->id,
        'sku' => 'SKU-1001-A',
        'product_name' => 'Sepatu Running Pro',
        'is_active' => false,
    ]);
});

test('super admin can delete a sku code', function () {
    $admin = User::factory()->create();
    $admin->assignRole('Super Admin');

    $skuCode = SkuCode::create([
        'sku' => 'SKU-1001',
        'product_name' => 'Sepatu Running',
        'brand' => 'ANTA',
        'default_value_of_product' => 550000,
        'is_active' => true,
    ]);

    $response = $this->actingAs($admin)->delete("/sku-codes/{$skuCode->id}");

    $response
        ->assertRedirect('/sku-codes')
        ->assertSessionHas('success', 'SKU Code berhasil dihapus.');

    $this->assertDatabaseMissing('sku_codes', [
        'id' => $skuCode->id,
    ]);
});
