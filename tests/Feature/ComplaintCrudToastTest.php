<?php

use App\Models\Brand;
use App\Models\CauseBy;
use App\Models\Complaint;
use App\Models\ComplaintPower;
use App\Models\ComplaintSource;
use App\Models\LastStep;
use App\Models\Oos;
use App\Models\Platform;
use App\Models\ReasonLateResponse;
use App\Models\ReasonWhitelist;
use App\Models\SkuCode;
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

    ComplaintSource::create([
        'name' => 'After Sales',
        'is_active' => true,
    ]);

    ComplaintPower::create([
        'name' => 'Hard Complaint',
        'is_active' => true,
    ]);

    ComplaintPower::create([
        'name' => 'Normal Complaint',
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
        'name' => 'CS',
        'is_active' => true,
    ]);

    SkuCode::create([
        'sku' => 'SKU-1001',
        'product_name' => 'Sepatu Running',
    ]);
});

function complaintPayload(array $overrides = []): array
{
    return array_merge([
        'source' => 'After Sales',
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
        'cs_name' => 'CS Test',
        'last_step' => 'Follow Up WH',
        'step_cs_selesai' => 'NO',
        'complaint_power' => 'Hard Complaint',
        'tanggal_update' => '2026-04-14',
    ], $overrides);
}

test('complaint create redirects back with a toast success message', function () {
    $user = User::factory()->create(['name' => 'CS Test']);
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
        'history' => 'complaint ke 1',
        'oos' => null,
    ]);
});

test('complaint history starts from complaint ke 1 and increments by username', function () {
    $user = User::factory()->create(['name' => 'CS Test']);
    $user->assignRole('CS');

    $this
        ->actingAs($user)
        ->from('/complaints')
        ->post('/complaints', complaintPayload([
            'order_id' => 'ORD-HISTORY-1',
            'resi' => 'RESI-HISTORY-1',
            'username' => 'repeat.customer',
        ]))
        ->assertRedirect('/complaints');

    $this
        ->actingAs($user)
        ->from('/complaints')
        ->post('/complaints', complaintPayload([
            'order_id' => 'ORD-HISTORY-2',
            'resi' => 'RESI-HISTORY-2',
            'username' => 'repeat.customer',
        ]))
        ->assertRedirect('/complaints');

    $this->assertDatabaseHas('complaints', [
        'order_id' => 'ORD-HISTORY-1',
        'history' => 'complaint ke 1',
    ]);

    $this->assertDatabaseHas('complaints', [
        'order_id' => 'ORD-HISTORY-2',
        'history' => 'complaint ke 2',
    ]);
});

test('complaint update redirects back with a toast success message', function () {
    $user = User::factory()->create(['name' => 'CS Test']);
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
    $user = User::factory()->create(['name' => 'CS Test']);
    $user->assignRole('CS');

    $response = $this
        ->actingAs($user)
        ->from('/complaints')
        ->post('/complaints', complaintPayload([
            'order_id' => 'ORD-WHITELIST-1',
            'resi' => 'RESI-WHITELIST-1',
            'last_step' => 'Claim Reject',
            'reason_whitelist' => 'Late Respons',
            'reason_late_respons' => 'CS',
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
        'reason_late_respons' => 'CS',
    ]);
});

test('complaint can autofill product name from sku master', function () {
    $user = User::factory()->create(['name' => 'CS Test']);
    $user->assignRole('CS');

    $response = $this
        ->actingAs($user)
        ->from('/complaints')
        ->post('/complaints', complaintPayload([
            'order_id' => 'ORD-SKU-AUTO-1',
            'resi' => 'RESI-SKU-AUTO-1',
            'product_name' => '',
        ]));

    $response
        ->assertRedirect('/complaints')
        ->assertSessionHas('success', 'Complaint berhasil dibuat.');

    $this->assertDatabaseHas('complaints', [
        'order_id' => 'ORD-SKU-AUTO-1',
        'sku' => 'SKU-1001',
        'product_name' => 'Sepatu Running',
        'brand' => 'ANTA',
    ]);
});

test('complaint stores manual part of bad input without master data dependency', function () {
    $user = User::factory()->create(['name' => 'CS Test']);
    $user->assignRole('CS');

    $response = $this
        ->actingAs($user)
        ->from('/complaints')
        ->post('/complaints', complaintPayload([
            'order_id' => 'ORD-PART-OF-BAD-1',
            'resi' => 'RESI-PART-OF-BAD-1',
            'part_of_bad' => 'Upper mesh sobek',
        ]));

    $response
        ->assertRedirect('/complaints')
        ->assertSessionHas('success', 'Complaint berhasil dibuat.');

    $this->assertDatabaseHas('complaints', [
        'order_id' => 'ORD-PART-OF-BAD-1',
        'part_of_bad' => 'Upper mesh sobek',
    ]);
});

test('complaint marks oos history only when order exists in oos data', function () {
    $user = User::factory()->create(['name' => 'CS Test']);
    $user->assignRole('CS');

    Oos::create([
        'tanggal_input' => '2026-04-14',
        'order_id' => 'ORD-OOS-1',
        'brand' => 'ANTA',
        'platform' => 'SHOPEE',
        'product_name' => 'Sepatu Running',
        'sku' => 'SKU-1001',
    ]);

    $response = $this
        ->actingAs($user)
        ->from('/complaints')
        ->post('/complaints', complaintPayload([
            'order_id' => 'ORD-OOS-1',
            'resi' => 'RESI-OOS-1',
        ]));

    $response
        ->assertRedirect('/complaints')
        ->assertSessionHas('success', 'Complaint berhasil dibuat.');

    $this->assertDatabaseHas('complaints', [
        'order_id' => 'ORD-OOS-1',
        'oos' => 'Ada Riwayat OOS',
    ]);
});

test('complaint delete redirects back with a toast success message', function () {
    $user = User::factory()->create(['name' => 'CS Test']);
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
        ->assertSessionHas('success', 'Complaint berhasil diarsipkan.');

    $this->assertSoftDeleted('complaints', [
        'id' => $complaint->id,
    ]);
});

test('complaint index can filter by brand and priority', function () {
    $user = User::factory()->create(['name' => 'CS Test']);
    $user->assignRole('CS');

    Complaint::create(complaintPayload([
        'order_id' => 'ORD-P1-ANTA',
        'resi' => 'RESI-P1-ANTA',
        'brand' => 'ANTA',
        'last_step' => 'Follow Up WH',
    ]));

    Complaint::create(complaintPayload([
        'order_id' => 'ORD-COOL-KAPPA',
        'resi' => 'RESI-COOL-KAPPA',
        'brand' => 'KAPPA',
        'last_step' => 'Seller Win',
    ]));

    $response = $this
        ->actingAs($user)
        ->get('/complaints?brand=ANTA&priority=P1');

    $response
        ->assertOk()
        ->assertSee('ORD-P1-ANTA')
        ->assertDontSee('ORD-COOL-KAPPA');
});
