<?php

use App\Models\Complaint;
use App\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Permission::findOrCreate('access complaints');

    Role::findOrCreate('CS')->syncPermissions([
        'access complaints',
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
