<?php

use App\Models\BadReview;
use App\Models\Complaint;
use App\Models\Oos;
use App\Models\OrderTracking;
use App\Http\Controllers\BadReviewController;
use App\Http\Controllers\BrandController;
use App\Http\Controllers\ComplaintController;
use App\Http\Controllers\LogisticController;
use App\Http\Controllers\LastStepController;
use App\Http\Controllers\OosController;
use App\Http\Controllers\OrderTrackingController;
use App\Http\Controllers\PlatformController;
use App\Http\Controllers\SubCaseController;
use App\Http\Controllers\UserManagementController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
})->name('home');

// Grup Route Backend internal. Cukup login, tanpa verifikasi email.
Route::middleware(['auth', 'active', 'password.reset.required'])->group(function () {
    Route::middleware('permission:view dashboard')->group(function () {
        Route::get('dashboard', function () {
            return \Inertia\Inertia::render('Dashboard', [
                'stats' => [
                    [
                        'label' => 'Complaints',
                        'value' => Complaint::query()->count(),
                        'helper' => Complaint::query()->where('status', 'Pending')->count() . ' pending',
                        'tone' => 'blue',
                    ],
                    [
                        'label' => 'Bad Reviews',
                        'value' => BadReview::query()->count(),
                        'helper' => BadReview::query()->where('status', 'Solved')->count() . ' solved',
                        'tone' => 'violet',
                    ],
                    [
                        'label' => 'Order Tracking',
                        'value' => OrderTracking::query()->count(),
                        'helper' => OrderTracking::query()->where('status', 'Pending')->count() . ' in progress',
                        'tone' => 'green',
                    ],
                    [
                        'label' => 'OOS Data',
                        'value' => Oos::query()->count(),
                        'helper' => 'Stock issue visibility',
                        'tone' => 'amber',
                    ],
                ],
                'recentComplaints' => Complaint::query()
                    ->latest()
                    ->limit(5)
                    ->get([
                        'id',
                        'order_id',
                        'username',
                        'brand',
                        'platform',
                        'status',
                        'priority',
                        'cs_name',
                        'updated_at',
                    ]),
            ]);
        })->name('dashboard');
    });

    Route::middleware('permission:access complaints')->group(function () {
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

    Route::prefix('logistics')->name('logistics.')->group(function () {
        Route::get('/', [LogisticController::class, 'index'])
            ->middleware('permission:view logistics')
            ->name('index');
        Route::post('/', [LogisticController::class, 'store'])
            ->middleware('permission:create logistics')
            ->name('store');
        Route::put('/{logistic}', [LogisticController::class, 'update'])
            ->middleware('permission:update logistics')
            ->name('update');
        Route::delete('/{logistic}', [LogisticController::class, 'destroy'])
            ->middleware('permission:delete logistics')
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

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
