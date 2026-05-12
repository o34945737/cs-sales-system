<?php

use App\Models\OrderTracking;
use App\Models\User;
use Spatie\Permission\Models\Permission;

function makeOrderTrackingForDelete(array $overrides = []): OrderTracking
{
    return OrderTracking::create(array_merge([
        'data_source' => 'WH',
        'tanggal_input' => '2026-05-12',
        'tanggal_order' => '2026-05-10',
        'brand' => 'ANTA',
        'platform' => 'Shopee',
        'order_id' => 'OT-DELETE-' . fake()->unique()->numberBetween(1000, 9999),
        'cause_by' => 'WH',
        'cs_name' => 'CS Alpha',
        'category' => 'Late Delivery',
        'last_step' => 'Waiting Claim',
        'tanggal_update' => '2026-05-12',
    ], $overrides));
}

beforeEach(function () {
    Permission::findOrCreate('access order trackings');
    Permission::findOrCreate('delete order trackings');
});

test('user without order tracking access cannot delete order tracking', function () {
    $user = User::factory()->create();
    $tracking = makeOrderTrackingForDelete();

    $this->actingAs($user)
        ->delete(route('order-trackings.destroy', $tracking))
        ->assertForbidden();

    $this->assertDatabaseHas('order_trackings', [
        'id' => $tracking->id,
    ]);
});

test('user with order tracking access can delete order tracking', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('access order trackings');
    $tracking = makeOrderTrackingForDelete();

    $this->actingAs($user)
        ->from(route('order-trackings.index'))
        ->delete(route('order-trackings.destroy', $tracking))
        ->assertRedirect(route('order-trackings.index'));

    $this->assertDatabaseMissing('order_trackings', [
        'id' => $tracking->id,
    ]);
});

test('bulk delete requires order tracking access', function () {
    $user = User::factory()->create();
    $tracking = makeOrderTrackingForDelete();

    $this->actingAs($user)
        ->post(route('order-trackings.bulk-delete'), [
            'ids' => [$tracking->id],
        ])
        ->assertForbidden();

    $this->assertDatabaseHas('order_trackings', [
        'id' => $tracking->id,
    ]);
});
