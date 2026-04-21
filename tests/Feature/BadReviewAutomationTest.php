<?php

use App\Models\Brand;
use App\Models\CauseBy;
use App\Models\Platform;
use App\Models\SkuCode;
use App\Models\SubCase;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Permission::findOrCreate('access bad reviews');

    Role::findOrCreate('CS')->syncPermissions([
        'access bad reviews',
    ]);

    Brand::create([
        'name' => 'ANTA',
        'is_active' => true,
    ]);

    Platform::create([
        'name' => 'SHOPEE',
        'is_active' => true,
    ]);

    SkuCode::create([
        'sku' => 'SKU-1001',
        'product_name' => 'Sepatu Running',
        'brand' => 'ANTA',
        'available_qty' => 8,
        'status_qty' => 'Ready',
        'is_active' => true,
    ]);

    foreach (['?', 'CS', 'BRAND'] as $name) {
        CauseBy::create([
            'name' => $name,
            'is_active' => true,
        ]);
    }

    SubCase::create([
        'name' => 'Bad Quality Product',
        'default_cause_by' => 'BRAND',
        'is_active' => true,
    ]);

    SubCase::create([
        'name' => 'Bad Service',
        'default_cause_by' => null,
        'is_active' => true,
    ]);
});

function badReviewPayload(array $overrides = []): array
{
    return array_merge([
        'tanggal_review' => '2026-04-21',
        'month' => 'April 2026',
        'brand' => 'ANTA',
        'platform' => 'SHOPEE',
        'order_id' => 'BR-1001',
        'username' => 'customer.bad',
        'star' => 1,
        'product_name' => 'Sepatu Running',
        'sku' => 'SKU-1001',
        'category_review' => 'Bad Service',
        'cause_by' => 'CS',
        'review_notes' => 'Customer memberikan review negatif.',
        'progress' => 'Follow Up Customer',
        'tanggal_update' => '2026-04-21 10:00:00',
        'cs_name' => 'CS Test',
    ], $overrides);
}

test('bad review stores pending status for follow up customer progress', function () {
    $user = User::factory()->create(['name' => 'CS Test', 'is_active' => true]);
    $user->assignRole('CS');

    $response = $this
        ->actingAs($user)
        ->from('/bad-reviews')
        ->post('/bad-reviews', badReviewPayload());

    $response
        ->assertRedirect('/bad-reviews')
        ->assertSessionHas('success', 'Bad Review berhasil disimpan.');

    $this->assertDatabaseHas('bad_reviews', [
        'order_id' => 'BR-1001',
        'progress' => 'Follow Up Customer',
        'status' => 'Pending',
        'cause_by' => 'CS',
    ]);
});

test('bad review stores solved status for auto reply progress', function () {
    $user = User::factory()->create(['name' => 'CS Test', 'is_active' => true]);
    $user->assignRole('CS');

    $response = $this
        ->actingAs($user)
        ->from('/bad-reviews')
        ->post('/bad-reviews', badReviewPayload([
            'order_id' => 'BR-AUTO-1',
            'progress' => 'Auto Reply',
        ]));

    $response
        ->assertRedirect('/bad-reviews')
        ->assertSessionHas('success', 'Bad Review berhasil disimpan.');

    $this->assertDatabaseHas('bad_reviews', [
        'order_id' => 'BR-AUTO-1',
        'progress' => 'Auto Reply',
        'status' => 'Solved',
    ]);
});

test('bad review requires cause by to match sub case default cause by', function () {
    $user = User::factory()->create(['name' => 'CS Test', 'is_active' => true]);
    $user->assignRole('CS');

    $response = $this
        ->actingAs($user)
        ->from('/bad-reviews')
        ->post('/bad-reviews', badReviewPayload([
            'order_id' => 'BR-MAPPED-1',
            'category_review' => 'Bad Quality Product',
            'cause_by' => 'CS',
        ]));

    $response
        ->assertRedirect('/bad-reviews')
        ->assertSessionHasErrors(['cause_by']);

    $this->assertDatabaseMissing('bad_reviews', [
        'order_id' => 'BR-MAPPED-1',
    ]);
});

test('bad review stores mapped cause by when sub case default is used', function () {
    $user = User::factory()->create(['name' => 'CS Test', 'is_active' => true]);
    $user->assignRole('CS');

    $response = $this
        ->actingAs($user)
        ->from('/bad-reviews')
        ->post('/bad-reviews', badReviewPayload([
            'order_id' => 'BR-MAPPED-2',
            'category_review' => 'Bad Quality Product',
            'cause_by' => 'BRAND',
        ]));

    $response
        ->assertRedirect('/bad-reviews')
        ->assertSessionHas('success', 'Bad Review berhasil disimpan.');

    $this->assertDatabaseHas('bad_reviews', [
        'order_id' => 'BR-MAPPED-2',
        'category_review' => 'Bad Quality Product',
        'cause_by' => 'BRAND',
    ]);
});

test('bad review can autofill product fields from active sku master', function () {
    $user = User::factory()->create(['name' => 'CS Test', 'is_active' => true]);
    $user->assignRole('CS');

    $response = $this
        ->actingAs($user)
        ->from('/bad-reviews')
        ->post('/bad-reviews', badReviewPayload([
            'order_id' => 'BR-SKU-AUTO-1',
            'product_name' => '',
            'brand' => 'ANTA',
        ]));

    $response
        ->assertRedirect('/bad-reviews')
        ->assertSessionHas('success', 'Bad Review berhasil disimpan.');

    $this->assertDatabaseHas('bad_reviews', [
        'order_id' => 'BR-SKU-AUTO-1',
        'sku' => 'SKU-1001',
        'product_name' => 'Sepatu Running',
        'brand' => 'ANTA',
        'status' => 'Pending',
        'month' => 'April 2026',
    ]);
});

test('bad review rejects brand mismatch for active sku master', function () {
    Brand::create([
        'name' => 'KAPPA',
        'is_active' => true,
    ]);

    $user = User::factory()->create(['name' => 'CS Test', 'is_active' => true]);
    $user->assignRole('CS');

    $response = $this
        ->actingAs($user)
        ->from('/bad-reviews')
        ->post('/bad-reviews', badReviewPayload([
            'order_id' => 'BR-SKU-MISMATCH-1',
            'brand' => 'KAPPA',
        ]));

    $response
        ->assertRedirect('/bad-reviews')
        ->assertSessionHasErrors(['brand']);

    $this->assertDatabaseMissing('bad_reviews', [
        'order_id' => 'BR-SKU-MISMATCH-1',
    ]);
});

test('bad review index uses filtered summaries and safe sort fallback', function () {
    $user = User::factory()->create(['name' => 'CS Test', 'is_active' => true]);
    $user->assignRole('CS');

    \App\Models\BadReview::create(badReviewPayload([
        'order_id' => 'BR-FILTER-1',
        'brand' => 'ANTA',
        'progress' => 'Follow Up Customer',
    ]));

    Brand::create([
        'name' => 'KAPPA',
        'is_active' => true,
    ]);

    \App\Models\BadReview::create(badReviewPayload([
        'order_id' => 'BR-FILTER-2',
        'brand' => 'KAPPA',
        'progress' => 'Auto Reply',
    ]));

    $response = $this
        ->actingAs($user)
        ->get('/bad-reviews?brand=ANTA&sort=drop_table');

    $response
        ->assertOk()
        ->assertInertia(fn(Assert $page) => $page
            ->component('BadReviews/Index')
            ->where('filters.brand', 'ANTA')
            ->where('statusSummary.all', 1)
            ->where('statusSummary.pending', 1)
            ->where('statusSummary.solved', 0)
            ->where('badReviews.data.0.order_id', 'BR-FILTER-1'));
});
