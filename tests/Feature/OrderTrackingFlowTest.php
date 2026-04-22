<?php

use App\Models\Brand;
use App\Models\Complaint;
use App\Models\JetTrackEntry;
use App\Models\LastStep;
use App\Models\Logistic;
use App\Models\OrderTracking;
use App\Models\OrderTrackingDataSource;
use App\Models\OrderTrackingRgoEntry;
use App\Models\Platform;
use App\Models\ReasonLateResponse;
use App\Models\ReasonWhitelist;
use App\Models\SubCase;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Permission::findOrCreate('access order trackings');

    Role::findOrCreate('WH')->syncPermissions([
        'access order trackings',
    ]);

    Role::findOrCreate('CS');

    OrderTrackingDataSource::create([
        'name' => 'WH',
        'is_active' => true,
    ]);

    OrderTrackingDataSource::create([
        'name' => 'Finance',
        'is_active' => true,
    ]);

    Brand::create([
        'name' => 'ANTA',
        'is_active' => true,
    ]);

    Platform::create([
        'name' => 'Lazada',
        'is_active' => true,
    ]);

    Platform::create([
        'name' => 'Shopee',
        'is_active' => true,
    ]);

    Logistic::create([
        'name' => 'JNE',
        'is_active' => true,
    ]);

    SubCase::create([
        'name' => 'Wrong Item',
        'default_cause_by' => 'WH',
        'is_active' => true,
    ]);

    LastStep::create([
        'name' => 'Claim Reject',
        'status_result' => 'Whitelist',
        'priority_level' => 'Mines',
        'is_active' => true,
    ]);

    LastStep::create([
        'name' => 'Waiting Claim',
        'status_result' => 'Pending',
        'priority_level' => 'P7',
        'is_active' => true,
    ]);

    ReasonWhitelist::create([
        'name' => 'Late Respons',
        'is_active' => true,
    ]);

    ReasonLateResponse::create([
        'name' => 'Slow Confirmation',
        'is_active' => true,
    ]);

    $this->warehouseUser = User::factory()->create([
        'name' => 'Warehouse Tester',
        'is_active' => true,
    ]);
    $this->warehouseUser->assignRole('WH');

    $this->csUser = User::factory()->create([
        'name' => 'CS Alpha',
        'is_active' => true,
    ]);
    $this->csUser->assignRole('CS');
});

function orderTrackingPayload(array $overrides = []): array
{
    return array_merge([
        'data_source' => 'WH',
        'tanggal_input' => '2026-04-21',
        'tanggal_order' => '2026-04-10',
        'brand' => 'ANTA',
        'platform' => 'Lazada',
        'order_id' => 'OT-1001',
        'value' => 250000,
        'logistics' => 'JNE',
        'awb' => 'AWB-1001',
        'erp_status' => 'Open Case',
        'payment_method' => 'COD',
        'wh_note' => 'Paket diterima retur oleh gudang.',
        'cs_name' => 'CS Alpha',
        'category' => 'Wrong Item',
        'last_step' => 'Claim Reject',
        'update' => 'Menunggu final handling.',
        'tanggal_update' => '2026-04-21',
        'value_receive' => 125000,
        'insurance_info' => 'Y',
        'update_wh' => 'WH sudah cek fisik barang.',
        'update_finance' => 'Finance menunggu dokumen final.',
        'reason_whitelist' => 'Late Respons',
        'reason_late_respons' => 'Slow Confirmation',
    ], $overrides);
}

test('order tracking index loads master driven options and can filter by source', function () {
    OrderTracking::create([
        'data_source' => 'WH',
        'tanggal_input' => '2026-04-21',
        'tanggal_order' => '2026-04-10',
        'brand' => 'ANTA',
        'platform' => 'Lazada',
        'order_id' => 'OT-WH-1',
        'logistics' => 'JNE',
        'cs_name' => 'CS Alpha',
        'category' => 'Wrong Item',
        'last_step' => 'Waiting Claim',
        'tanggal_update' => '2026-04-21',
    ]);

    OrderTracking::create([
        'data_source' => 'Finance',
        'tanggal_input' => '2026-04-21',
        'tanggal_order' => '2026-04-10',
        'brand' => 'ANTA',
        'platform' => 'Shopee',
        'order_id' => 'OT-FIN-1',
        'logistics' => 'JNE',
        'cs_name' => 'CS Alpha',
        'category' => 'Wrong Item',
        'last_step' => 'Waiting Claim',
        'tanggal_update' => '2026-04-21',
    ]);

    $response = $this
        ->actingAs($this->warehouseUser)
        ->get('/order-trackings?source=WH');

    $response
        ->assertOk()
        ->assertInertia(fn(Assert $page) => $page
            ->component('OrderTrackings/Index')
            ->where('filters.source', 'WH')
            ->where('orderTrackings.data.0.order_id', 'OT-WH-1')
            ->where('orderTrackings.data.0.source', 'WH')
            ->has('sourceOptions', 2)
            ->has('brandOptions', 1)
            ->has('platformOptions', 2)
            ->has('logisticsOptions', 1)
            ->has('categoryOptions', 1)
            ->has('lastStepOptions', 2)
            ->has('reasonWhitelistOptions', 1)
            ->has('reasonLateResponseOptions', 1)
            ->has('rgoOrderIds')
            ->has('jetTrackMap'));
});

test('order tracking stores mapped source and automation fields from the flow', function () {
    Complaint::create([
        'order_id' => 'OT-MERGER-1',
    ]);

    $response = $this
        ->actingAs($this->warehouseUser)
        ->from('/order-trackings')
        ->post('/order-trackings', orderTrackingPayload([
            'order_id' => 'OT-MERGER-1',
        ]));

    $response
        ->assertRedirect('/order-trackings')
        ->assertSessionHas('success', 'Order Tracking berhasil disimpan.');

    $this->assertDatabaseHas('order_trackings', [
        'order_id' => 'OT-MERGER-1',
        'data_source' => 'WH',
        'status' => 'Whitelist',
        'month' => 'April 2026',
        'tanggal_tts' => '2026-05-04',
        'automation_track' => 'MERGER',
        'reason_whitelist' => 'Late Respons',
        'reason_late_respons' => 'Slow Confirmation',
    ]);
});

test('order tracking stores rgo automation when order id exists in rgo list', function () {
    OrderTrackingRgoEntry::create([
        'order_id' => 'OT-RGO-1',
        'is_active' => true,
    ]);

    $response = $this
        ->actingAs($this->warehouseUser)
        ->from('/order-trackings')
        ->post('/order-trackings', orderTrackingPayload([
            'order_id' => 'OT-RGO-1',
            'last_step' => 'Waiting Claim',
            'reason_whitelist' => null,
            'reason_late_respons' => null,
        ]));

    $response
        ->assertRedirect('/order-trackings')
        ->assertSessionHas('success', 'Order Tracking berhasil disimpan.');

    $this->assertDatabaseHas('order_trackings', [
        'order_id' => 'OT-RGO-1',
        'automation_track' => 'Sudah diRGO',
        'kondisi_barang' => null,
        'status' => 'Pending',
    ]);
});

test('order tracking stores jet track automation and kondisi barang when awb exists in jet track list', function () {
    JetTrackEntry::create([
        'awb' => 'AWB-JET-1',
        'kondisi_barang' => 'Box penyok',
        'is_active' => true,
    ]);

    $response = $this
        ->actingAs($this->warehouseUser)
        ->from('/order-trackings')
        ->post('/order-trackings', orderTrackingPayload([
            'order_id' => 'OT-JET-1',
            'awb' => 'AWB-JET-1',
            'last_step' => 'Waiting Claim',
            'reason_whitelist' => null,
            'reason_late_respons' => null,
        ]));

    $response
        ->assertRedirect('/order-trackings')
        ->assertSessionHas('success', 'Order Tracking berhasil disimpan.');

    $this->assertDatabaseHas('order_trackings', [
        'order_id' => 'OT-JET-1',
        'awb' => 'AWB-JET-1',
        'automation_track' => 'ADA DI JET TRACK - Box penyok',
        'kondisi_barang' => 'Box penyok',
        'status' => 'Pending',
    ]);
});

test('order tracking update clears whitelist reasons when last step changes', function () {
    $orderTracking = OrderTracking::create([
        'data_source' => 'WH',
        'tanggal_input' => '2026-04-21',
        'tanggal_order' => '2026-04-10',
        'brand' => 'ANTA',
        'platform' => 'Lazada',
        'order_id' => 'OT-UPDATE-1',
        'logistics' => 'JNE',
        'cs_name' => 'CS Alpha',
        'category' => 'Wrong Item',
        'last_step' => 'Claim Reject',
        'tanggal_update' => '2026-04-21',
        'reason_whitelist' => 'Late Respons',
        'reason_late_respons' => 'Slow Confirmation',
    ]);

    $response = $this
        ->actingAs($this->warehouseUser)
        ->from('/order-trackings')
        ->put("/order-trackings/{$orderTracking->id}", orderTrackingPayload([
            'data_source' => 'Finance',
            'platform' => 'Shopee',
            'order_id' => 'OT-UPDATE-2',
            'last_step' => 'Waiting Claim',
            'reason_whitelist' => 'Late Respons',
            'reason_late_respons' => 'Slow Confirmation',
        ]));

    $response
        ->assertRedirect('/order-trackings')
        ->assertSessionHas('success', 'Order Tracking berhasil diperbarui.');

    $this->assertDatabaseHas('order_trackings', [
        'id' => $orderTracking->id,
        'order_id' => 'OT-UPDATE-2',
        'data_source' => 'Finance',
        'platform' => 'Shopee',
        'status' => 'Pending',
        'reason_whitelist' => null,
        'reason_late_respons' => null,
        'automation_track' => null,
    ]);
});
