<?php

use App\Models\Brand;
use App\Models\CauseBy;
use App\Models\Complaint;
use App\Models\LastStep;
use App\Models\Platform;
use App\Models\ReasonLateResponse;
use App\Models\ReasonWhitelist;
use App\Models\SubCase;
use App\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Permission::findOrCreate('access complaints');

    Role::findOrCreate('CS')->syncPermissions([
        'access complaints',
    ]);

    Brand::create([
        'name' => 'ANTA',
        'is_active' => true,
    ]);

    Platform::create([
        'name' => 'SHOPEE',
        'is_active' => true,
    ]);

    CauseBy::create([
        'name' => 'CS',
        'is_active' => true,
    ]);

    SubCase::create([
        'name' => 'Bad Service',
        'default_cause_by' => null,
        'is_active' => true,
    ]);

    LastStep::create([
        'name' => 'Follow Up WH',
        'status_result' => 'Pending',
        'priority_level' => 'P1',
        'is_active' => true,
    ]);

    LastStep::create([
        'name' => 'Seller Win',
        'status_result' => 'Solved',
        'priority_level' => 'Cool',
        'is_active' => true,
    ]);

    LastStep::create([
        'name' => 'Claim Reject',
        'status_result' => 'Whitelist',
        'priority_level' => 'Mines',
        'is_active' => true,
    ]);

    ReasonWhitelist::create([
        'name' => 'Late Respons',
        'is_active' => true,
    ]);

    ReasonLateResponse::create([
        'name' => 'No update from warehouse',
        'is_active' => true,
    ]);
});

function complaintPayload(array $overrides = []): array
{
    return array_merge([
        'source' => 'AFTERSALES',
        'tanggal_complaint' => '2026-04-14',
        'tanggal_order' => '2026-04-13',
        'jam_customer_complaint' => '10:00:00',
        'brand' => 'ANTA',
        'platform' => 'SHOPEE',
        'order_id' => 'ORD-1001',
        'username' => 'customer.test',
        'resi' => 'RESI-1001',
        'sku' => 'SKU-1001',
        'sub_case' => 'Bad Service',
        'cause_by' => 'CS',
        'summary_case' => 'Customer menerima respons yang terlambat.',
        'update_long_text' => 'Tim CS melakukan follow up ke customer.',
        'cs_name' => 'TYAS',
        'last_step' => 'Follow Up WH',
        'step_cs_selesai' => 'NO',
        'tanggal_update' => '2026-04-14',
    ], $overrides);
}

test('complaint create redirects back with a toast success message', function () {
    $user = User::factory()->create();
    $user->assignRole('CS');

    $response = $this
        ->actingAs($user)
        ->from('/complaints')
        ->post('/complaints', complaintPayload());

    $response
        ->assertRedirect('/complaints')
        ->assertSessionHas('success', 'Complaint berhasil dibuat.');

    $this->assertDatabaseHas('complaints', [
        'order_id' => 'ORD-1001',
        'username' => 'customer.test',
        'status' => 'Pending',
        'priority' => 'P1',
        'oos' => 'Tidak Ada Riwayat OOS',
    ]);
});

test('complaint update redirects back with a toast success message', function () {
    $user = User::factory()->create();
    $user->assignRole('CS');

    $complaint = Complaint::create(complaintPayload());

    $response = $this
        ->actingAs($user)
        ->from('/complaints')
        ->put("/complaints/{$complaint->id}", [
            'last_step' => 'Seller Win',
            'step_cs_selesai' => 'YES',
            'tanggal_step_cs_selesai' => '2026-04-14',
            'proof' => 'Percakapan lanjutan customer.',
        ]);

    $response
        ->assertRedirect('/complaints')
        ->assertSessionHas('success', 'Complaint berhasil diperbarui.');

    $this->assertDatabaseHas('complaints', [
        'id' => $complaint->id,
        'last_step' => 'Seller Win',
        'step_cs_selesai' => 'YES',
        'status' => 'Solved',
        'priority' => 'Cool',
    ]);
});

test('complaint claim reject flow stores whitelist details from master data', function () {
    $user = User::factory()->create();
    $user->assignRole('CS');

    $response = $this
        ->actingAs($user)
        ->from('/complaints')
        ->post('/complaints', complaintPayload([
            'order_id' => 'ORD-WHITELIST-1',
            'resi' => 'RESI-WHITELIST-1',
            'last_step' => 'Claim Reject',
            'reason_whitelist' => 'Late Respons',
            'reason_late_respons' => 'No update from warehouse',
        ]));

    $response
        ->assertRedirect('/complaints')
        ->assertSessionHas('success', 'Complaint berhasil dibuat.');

    $this->assertDatabaseHas('complaints', [
        'order_id' => 'ORD-WHITELIST-1',
        'last_step' => 'Claim Reject',
        'status' => 'Whitelist',
        'priority' => 'Mines',
        'reason_whitelist' => 'Late Respons',
        'reason_late_respons' => 'No update from warehouse',
    ]);
});

test('complaint delete redirects back with a toast success message', function () {
    $user = User::factory()->create();
    $user->assignRole('CS');

    $complaint = Complaint::create(complaintPayload([
        'order_id' => 'ORD-DELETE-1',
        'resi' => 'RESI-DELETE-1',
    ]));

    $response = $this
        ->actingAs($user)
        ->from('/complaints')
        ->delete("/complaints/{$complaint->id}");

    $response
        ->assertRedirect('/complaints')
        ->assertSessionHas('success', 'Complaint berhasil dihapus.');

    $this->assertDatabaseMissing('complaints', [
        'id' => $complaint->id,
    ]);
});
