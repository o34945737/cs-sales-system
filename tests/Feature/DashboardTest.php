<?php

use App\Models\BadReview;
use App\Models\Complaint;
use App\Models\Oos;
use App\Models\OrderTracking;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Permission;

function dashboardUser(): User
{
    Permission::findOrCreate('view dashboard');

    $user = User::factory()->create();
    $user->givePermissionTo('view dashboard');

    return $user;
}

test('guests are redirected to the login page', function () {
    $response = $this->get('/dashboard');

    $response->assertRedirect('/login');
});

test('authenticated users can visit the overview dashboard page', function () {
    $user = dashboardUser();
    $this->actingAs($user);

    $response = $this->get('/dashboard');

    $response
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard/Overview'));
});

test('overview dashboard shows points one to four plus monthly agent recap', function () {
    $user = dashboardUser();
    $this->actingAs($user);

    Complaint::create([
        'status' => 'Pending',
        'cs_name' => 'CS A',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    Complaint::create([
        'status' => 'Solved',
        'cs_name' => 'CS A',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    OrderTracking::create([
        'status' => 'Pending',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    Oos::create([
        'tanggal_input' => now()->toDateString(),
        'brand' => 'ANTA',
    ]);

    $response = $this->get('/dashboard');

    $response
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard/Overview')
            ->where('pendingComplaintCount', 1)
            ->where('pendingOtCount', 1)
            ->where('oosTodayCount', 1)
            ->where('totalTaskCount', 3)
            ->where('agentRecap.0.agent', 'CS A')
            ->where('agentRecap.0.total', 2)
            ->where('agentRecap.0.solved', 1)
            ->where('agentRecap.0.pending', 1)
            ->missing('productivity')
            ->missing('today'));
});

test('complaint analytics dashboard is available from the dashboard submenu', function () {
    $user = dashboardUser();
    $this->actingAs($user);

    Complaint::create([
        'status' => 'Pending',
        'platform' => 'Shopee',
        'cause_by' => 'WH',
        'complaint_power' => 'Hard Complaint',
        'sub_case' => 'Wrong Item',
        'brand' => 'ANTA',
        'cs_name' => 'CS A',
        'last_step' => 'Mediasi',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $response = $this->get('/dashboard/complaints');

    $response
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard/ComplaintAnalytics')
            ->where('pendingByPlatform.0.label', 'Shopee')
            ->where('pendingByCauseBy.0.label', 'WH')
            ->where('pendingByLevel.0.label', 'Hard Complaint')
            ->where('pendingBySubCase.0.label', 'Wrong Item')
            ->where('brandRealTime.0.label', 'ANTA')
            ->where('complaintByStatus.0.label', 'Pending')
            ->where('complaintByStatus.0.total', 1)
            ->where('pendingByLastStep.0.label', 'Mediasi')
            ->where('totalComplaintCount', 1));
});

test('performance monitor dashboard is available from the dashboard submenu', function () {
    $user = dashboardUser();
    $this->actingAs($user);

    BadReview::create([
        'tanggal_review' => now()->toDateString(),
        'brand' => 'ANTA',
        'progress' => 'Follow Up Customer',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    Oos::create([
        'tanggal_input' => now()->toDateString(),
        'brand' => 'KAPPA',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $response = $this->get('/dashboard/performance');

    $response
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard/PerformanceMonitoring')
            ->where('badReviewByBrand.0.label', 'ANTA')
            ->where('badReviewByBrand.0.total', 1)
            ->where('oosByBrand.0.label', 'KAPPA')
            ->where('oosByBrand.0.total', 1));
});
