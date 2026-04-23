<?php

use App\Models\BadReview;
use App\Models\Complaint;
use App\Models\DailyProductivity;
use App\Models\LastStep;
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

test('overview dashboard shows points one to four plus combined agent recap', function () {
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
        'cs_name' => 'CS A',
    ]);

    DailyProductivity::create([
        'cs_name' => 'CS A',
        'tanggal' => now()->toDateString(),
        'complaint_handled' => 1,
        'complaint_solved' => 1,
        'bad_review_handled' => 0,
        'order_tracking_handled' => 1,
        'oos_handled' => 0,
        'total_ticket' => 2,
        'notes' => 'Shift pagi',
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
            ->where('agentRecap.0.distributed', 3)
            ->where('agentRecap.0.handled', 2)
            ->where('agentRecap.0.solved', 1)
            ->where('agentRecap.0.productivity_total', 2)
            ->where('agentDdayStats.0.dist_oos', 1)
            ->where('todayProductivity.0.cs_name', 'CS A')
            ->where('today', now()->toDateString()));
});

test('complaint analytics dashboard is available from the dashboard submenu', function () {
    $user = dashboardUser();
    $this->actingAs($user);

    LastStep::create([
        'name' => 'Mediasi',
        'status_result' => 'Pending',
        'priority_level' => 'P2',
        'type' => 'External',
        'is_active' => true,
    ]);

    Complaint::create([
        'status' => 'Pending',
        'platform' => 'Shopee',
        'cause_by' => 'WH',
        'complaint_power' => 'Hard Complaint',
        'sub_case' => 'Wrong Item',
        'tanggal_complaint' => now()->subDays(2)->toDateString(),
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
            ->where('pendingBySubCase.0.sla_ok', 0)
            ->where('pendingBySubCase.0.sla_breach', 1)
            ->where('brandRealTime.0.label', 'ANTA')
            ->where('complaintByStatus.0.label', 'Pending')
            ->where('complaintByStatus.0.total', 1)
            ->where('pendingByLastStep.0.label', 'Mediasi')
            ->where('pendingByLastStepExternal.0.label', 'Mediasi')
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

test('dashboard agent interface page is available from the dashboard submenu', function () {
    $user = dashboardUser();
    $this->actingAs($user);

    LastStep::create([
        'name' => 'Follow Up WH',
        'status_result' => 'Pending',
        'priority_level' => 'P1',
        'type' => 'Internal',
        'is_active' => true,
    ]);

    LastStep::create([
        'name' => 'Seller Win',
        'status_result' => 'Solved',
        'priority_level' => 'Cool',
        'type' => null,
        'is_active' => true,
    ]);

    Complaint::create([
        'status' => 'Pending',
        'cs_name' => 'CS A',
        'last_step' => 'Follow Up WH',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    Complaint::create([
        'status' => 'Solved',
        'cs_name' => 'CS A',
        'last_step' => 'Seller Win',
        'created_at' => now()->subDay(),
        'updated_at' => now(),
    ]);

    $response = $this->get('/dashboard/agents');

    $response
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard/AgentInterface')
            ->where('agentInterface.0.agent', 'CS A')
            ->where('agentInterface.0.pending', 1)
            ->where('agentInterface.0.solved_total', 1)
            ->where('pendingByAgentPriority.0.agent', 'CS A')
            ->where('pendingByAgentPriority.0.priorities.0.priority', 'P1'));
});

test('dashboard productivity can be stored', function () {
    $user = dashboardUser();
    $this->actingAs($user);

    $response = $this->post('/dashboard/productivity', [
        'cs_name' => 'CS B',
        'tanggal' => now()->toDateString(),
        'complaint_handled' => 2,
        'complaint_solved' => 1,
        'bad_review_handled' => 1,
        'order_tracking_handled' => 3,
        'oos_handled' => 0,
        'notes' => 'Closing shift',
    ]);

    $response
        ->assertRedirect()
        ->assertSessionHas('success', 'Productivity harian berhasil disimpan.');

    $this->assertDatabaseHas('daily_productivities', [
        'cs_name' => 'CS B',
        'tanggal' => now()->toDateString() . ' 00:00:00',
        'complaint_handled' => 2,
        'complaint_solved' => 1,
        'bad_review_handled' => 1,
        'order_tracking_handled' => 3,
        'oos_handled' => 0,
        'total_ticket' => 6,
        'notes' => 'Closing shift',
    ]);
});
