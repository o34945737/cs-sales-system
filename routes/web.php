<?php

use App\Http\Controllers\BadReviewController;
use App\Http\Controllers\BrandController;
use App\Http\Controllers\ComplaintController;
use App\Http\Controllers\ComplaintPowerController;
use App\Http\Controllers\ComplaintSourceController;
use App\Http\Controllers\ComplaintStepStatusController;
use App\Http\Controllers\CauseByController;
use App\Http\Controllers\JetTrackEntryController;
use App\Http\Controllers\LastStepController;
use App\Http\Controllers\OosController;
use App\Http\Controllers\OosReasonController;
use App\Http\Controllers\OosSolutionController;
use App\Http\Controllers\OrderTrackingController;
use App\Http\Controllers\OrderTrackingDataSourceController;
use App\Http\Controllers\OrderTrackingErpStatusController;
use App\Http\Controllers\OrderTrackingRgoEntryController;
use App\Http\Controllers\PlatformController;
use App\Http\Controllers\ReasonLateResponseController;
use App\Http\Controllers\ReasonWhitelistController;
use App\Http\Controllers\SkuCodeController;
use App\Http\Controllers\SubCaseController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\UserManagementController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return Auth::check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
})->name('home');

// Grup Route Backend internal. Cukup login, tanpa verifikasi email.
Route::middleware(['auth', 'active', 'password.reset.required'])->group(function () {
    Route::middleware('permission:view dashboard')->group(function () {
        Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');
        Route::get('dashboard/complaints', [DashboardController::class, 'complaintAnalytics'])->name('dashboard.complaints');
        Route::get('dashboard/performance', [DashboardController::class, 'performanceMonitoring'])->name('dashboard.performance');
        Route::get('dashboard/agents', [DashboardController::class, 'agentInterface'])->name('dashboard.agents');
        Route::post('dashboard/productivity', [DashboardController::class, 'storeProductivity'])->name('dashboard.productivity.store');
    });

    Route::middleware('permission:access complaints')->group(function () {
        Route::get('complaints/history/{username}', [ComplaintController::class, 'getCustomerHistory'])->name('complaints.history');
        Route::post('complaints/bulk-delete', [ComplaintController::class, 'bulkDestroy'])
            ->name('complaints.bulk-delete');
        Route::resource('complaints', ComplaintController::class);
    });

    Route::middleware('permission:access bad reviews')->group(function () {
        Route::resource('bad-reviews', BadReviewController::class);
    });

    Route::middleware('permission:access order trackings')->group(function () {
        Route::resource('order-trackings', OrderTrackingController::class);
    });

    Route::middleware('permission:access oos')->group(function () {
        Route::resource('oos', OosController::class);
    });

    Route::resource('order-tracking-erp-statuses', OrderTrackingErpStatusController::class)
        ->parameters([
            'order-tracking-erp-statuses' => 'order_tracking_erp_status',
        ])
        ->only(['index', 'store', 'update', 'destroy']);
    Route::get('order-tracking-erp-statuses/template', [OrderTrackingErpStatusController::class, 'downloadTemplate'])
        ->middleware('permission:import order tracking erp statuses')
        ->name('order-tracking-erp-statuses.template');
    Route::post('order-tracking-erp-statuses/import', [OrderTrackingErpStatusController::class, 'import'])
        ->middleware('permission:import order tracking erp statuses')
        ->name('order-tracking-erp-statuses.import');

    Route::prefix('brands')->name('brands.')->group(function () {
        Route::get('/', [BrandController::class, 'index'])
            ->middleware('permission:view brands')
            ->name('index');
        Route::post('/', [BrandController::class, 'store'])
            ->middleware('permission:create brands')
            ->name('store');
        Route::put('/{brand}', [BrandController::class, 'update'])
            ->middleware('permission:update brands')
            ->name('update');
        Route::delete('/{brand}', [BrandController::class, 'destroy'])
            ->middleware('permission:delete brands')
            ->name('destroy');
    });

    Route::prefix('platforms')->name('platforms.')->group(function () {
        Route::get('/', [PlatformController::class, 'index'])
            ->middleware('permission:view platforms')
            ->name('index');
        Route::post('/', [PlatformController::class, 'store'])
            ->middleware('permission:create platforms')
            ->name('store');
        Route::put('/{platform}', [PlatformController::class, 'update'])
            ->middleware('permission:update platforms')
            ->name('update');
        Route::delete('/{platform}', [PlatformController::class, 'destroy'])
            ->middleware('permission:delete platforms')
            ->name('destroy');
    });

    Route::prefix('complaint-sources')->name('complaint-sources.')->group(function () {
        Route::get('/', [ComplaintSourceController::class, 'index'])
            ->middleware('permission:view complaint sources')
            ->name('index');
        Route::post('/', [ComplaintSourceController::class, 'store'])
            ->middleware('permission:create complaint sources')
            ->name('store');
        Route::put('/{complaintSource}', [ComplaintSourceController::class, 'update'])
            ->middleware('permission:update complaint sources')
            ->name('update');
        Route::delete('/{complaintSource}', [ComplaintSourceController::class, 'destroy'])
            ->middleware('permission:delete complaint sources')
            ->name('destroy');
    });

    Route::prefix('complaint-powers')->name('complaint-powers.')->group(function () {
        Route::get('/', [ComplaintPowerController::class, 'index'])
            ->middleware('permission:view complaint powers')
            ->name('index');
        Route::post('/', [ComplaintPowerController::class, 'store'])
            ->middleware('permission:create complaint powers')
            ->name('store');
        Route::put('/{complaintPower}', [ComplaintPowerController::class, 'update'])
            ->middleware('permission:update complaint powers')
            ->name('update');
        Route::delete('/{complaintPower}', [ComplaintPowerController::class, 'destroy'])
            ->middleware('permission:delete complaint powers')
            ->name('destroy');
    });

    Route::prefix('complaint-step-statuses')->name('complaint-step-statuses.')->group(function () {
        Route::get('/', [ComplaintStepStatusController::class, 'index'])
            ->middleware('permission:view complaint step statuses')
            ->name('index');
        Route::post('/', [ComplaintStepStatusController::class, 'store'])
            ->middleware('permission:create complaint step statuses')
            ->name('store');
        Route::put('/{complaintStepStatus}', [ComplaintStepStatusController::class, 'update'])
            ->middleware('permission:update complaint step statuses')
            ->name('update');
        Route::delete('/{complaintStepStatus}', [ComplaintStepStatusController::class, 'destroy'])
            ->middleware('permission:delete complaint step statuses')
            ->name('destroy');
    });

    Route::prefix('sku-codes')->name('sku-codes.')->group(function () {
        Route::get('/', [SkuCodeController::class, 'index'])
            ->middleware('permission:view sku codes')
            ->name('index');
        Route::post('/', [SkuCodeController::class, 'store'])
            ->middleware('permission:create sku codes')
            ->name('store');
        Route::put('/{skuCode}', [SkuCodeController::class, 'update'])
            ->middleware('permission:update sku codes')
            ->name('update');
        Route::delete('/{skuCode}', [SkuCodeController::class, 'destroy'])
            ->middleware('permission:delete sku codes')
            ->name('destroy');
    });


    Route::prefix('sub-cases')->name('sub-cases.')->group(function () {
        Route::get('/', [SubCaseController::class, 'index'])
            ->middleware('permission:view sub cases')
            ->name('index');
        Route::post('/', [SubCaseController::class, 'store'])
            ->middleware('permission:create sub cases')
            ->name('store');
        Route::put('/{subCase}', [SubCaseController::class, 'update'])
            ->middleware('permission:update sub cases')
            ->name('update');
        Route::delete('/{subCase}', [SubCaseController::class, 'destroy'])
            ->middleware('permission:delete sub cases')
            ->name('destroy');
    });

    Route::prefix('last-steps')->name('last-steps.')->group(function () {
        Route::get('/', [LastStepController::class, 'index'])
            ->middleware('permission:view last steps')
            ->name('index');
        Route::post('/', [LastStepController::class, 'store'])
            ->middleware('permission:create last steps')
            ->name('store');
        Route::put('/{lastStep}', [LastStepController::class, 'update'])
            ->middleware('permission:update last steps')
            ->name('update');
        Route::delete('/{lastStep}', [LastStepController::class, 'destroy'])
            ->middleware('permission:delete last steps')
            ->name('destroy');
    });

    Route::prefix('reason-whitelists')->name('reason-whitelists.')->group(function () {
        Route::get('/', [ReasonWhitelistController::class, 'index'])
            ->middleware('permission:view reason whitelists')
            ->name('index');
        Route::post('/', [ReasonWhitelistController::class, 'store'])
            ->middleware('permission:create reason whitelists')
            ->name('store');
        Route::put('/{reasonWhitelist}', [ReasonWhitelistController::class, 'update'])
            ->middleware('permission:update reason whitelists')
            ->name('update');
        Route::delete('/{reasonWhitelist}', [ReasonWhitelistController::class, 'destroy'])
            ->middleware('permission:delete reason whitelists')
            ->name('destroy');
    });

    Route::prefix('reason-late-responses')->name('reason-late-responses.')->group(function () {
        Route::get('/', [ReasonLateResponseController::class, 'index'])
            ->middleware('permission:view reason late responses')
            ->name('index');
        Route::post('/', [ReasonLateResponseController::class, 'store'])
            ->middleware('permission:create reason late responses')
            ->name('store');
        Route::put('/{reasonLateResponse}', [ReasonLateResponseController::class, 'update'])
            ->middleware('permission:update reason late responses')
            ->name('update');
        Route::delete('/{reasonLateResponse}', [ReasonLateResponseController::class, 'destroy'])
            ->middleware('permission:delete reason late responses')
            ->name('destroy');
    });

    Route::prefix('order-tracking-data-sources')->name('order-tracking-data-sources.')->group(function () {
        Route::get('/', [OrderTrackingDataSourceController::class, 'index'])
            ->middleware('permission:view order tracking data sources')
            ->name('index');
        Route::post('/', [OrderTrackingDataSourceController::class, 'store'])
            ->middleware('permission:create order tracking data sources')
            ->name('store');
        Route::put('/{orderTrackingDataSource}', [OrderTrackingDataSourceController::class, 'update'])
            ->middleware('permission:update order tracking data sources')
            ->name('update');
        Route::delete('/{orderTrackingDataSource}', [OrderTrackingDataSourceController::class, 'destroy'])
            ->middleware('permission:delete order tracking data sources')
            ->name('destroy');
    });

    Route::prefix('order-tracking-rgo-entries')->name('order-tracking-rgo-entries.')->group(function () {
        Route::get('/', [OrderTrackingRgoEntryController::class, 'index'])
            ->middleware('permission:view order tracking rgo entries')
            ->name('index');
        Route::post('/', [OrderTrackingRgoEntryController::class, 'store'])
            ->middleware('permission:create order tracking rgo entries')
            ->name('store');
        Route::get('/template', [OrderTrackingRgoEntryController::class, 'downloadTemplate'])
            ->middleware('permission:import order tracking rgo entries')
            ->name('template');
        Route::post('/import', [OrderTrackingRgoEntryController::class, 'import'])
            ->middleware('permission:import order tracking rgo entries')
            ->name('import');
        Route::put('/{orderTrackingRgoEntry}', [OrderTrackingRgoEntryController::class, 'update'])
            ->middleware('permission:update order tracking rgo entries')
            ->name('update');
        Route::delete('/{orderTrackingRgoEntry}', [OrderTrackingRgoEntryController::class, 'destroy'])
            ->middleware('permission:delete order tracking rgo entries')
            ->name('destroy');
    });

    Route::prefix('jet-track-entries')->name('jet-track-entries.')->group(function () {
        Route::get('/', [JetTrackEntryController::class, 'index'])
            ->middleware('permission:view jet track entries')
            ->name('index');
        Route::post('/', [JetTrackEntryController::class, 'store'])
            ->middleware('permission:create jet track entries')
            ->name('store');
        Route::put('/{jetTrackEntry}', [JetTrackEntryController::class, 'update'])
            ->middleware('permission:update jet track entries')
            ->name('update');
        Route::delete('/{jetTrackEntry}', [JetTrackEntryController::class, 'destroy'])
            ->middleware('permission:delete jet track entries')
            ->name('destroy');
    });

    Route::prefix('oos-reasons')->name('oos-reasons.')->group(function () {
        Route::get('/', [OosReasonController::class, 'index'])
            ->middleware('permission:view oos reasons')
            ->name('index');
        Route::post('/', [OosReasonController::class, 'store'])
            ->middleware('permission:create oos reasons')
            ->name('store');
        Route::put('/{oosReason}', [OosReasonController::class, 'update'])
            ->middleware('permission:update oos reasons')
            ->name('update');
        Route::delete('/{oosReason}', [OosReasonController::class, 'destroy'])
            ->middleware('permission:delete oos reasons')
            ->name('destroy');
    });

    Route::prefix('oos-solutions')->name('oos-solutions.')->group(function () {
        Route::get('/', [OosSolutionController::class, 'index'])
            ->middleware('permission:view oos solutions')
            ->name('index');
        Route::post('/', [OosSolutionController::class, 'store'])
            ->middleware('permission:create oos solutions')
            ->name('store');
        Route::put('/{oosSolution}', [OosSolutionController::class, 'update'])
            ->middleware('permission:update oos solutions')
            ->name('update');
        Route::delete('/{oosSolution}', [OosSolutionController::class, 'destroy'])
            ->middleware('permission:delete oos solutions')
            ->name('destroy');
    });

    Route::prefix('cause-bys')->name('cause-bys.')->group(function () {
        Route::get('/', [CauseByController::class, 'index'])
            ->middleware('permission:view cause bys')
            ->name('index');
        Route::post('/', [CauseByController::class, 'store'])
            ->middleware('permission:create cause bys')
            ->name('store');
        Route::put('/{causeBy}', [CauseByController::class, 'update'])
            ->middleware('permission:update cause bys')
            ->name('update');
        Route::delete('/{causeBy}', [CauseByController::class, 'destroy'])
            ->middleware('permission:delete cause bys')
            ->name('destroy');
    });

    Route::prefix('users')->name('users.')->group(function () {
        Route::get('/', [UserManagementController::class, 'index'])
            ->middleware('permission:view users')
            ->name('index');
        Route::post('/', [UserManagementController::class, 'store'])
            ->middleware('permission:create users')
            ->name('store');
        Route::put('/{user}', [UserManagementController::class, 'update'])
            ->middleware('permission:update users')
            ->name('update');
        Route::delete('/{user}', [UserManagementController::class, 'destroy'])
            ->middleware('permission:delete users')
            ->name('destroy');
    });
});

require __DIR__ . '/settings.php';
require __DIR__ . '/auth.php';
