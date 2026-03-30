<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

use App\Http\Controllers\ComplaintController;
use App\Http\Controllers\BadReviewController;
use App\Http\Controllers\OrderTrackingController;
use App\Http\Controllers\OosController;

Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

// Grup Route Backend (Hanya Bisa Diakses Jika User Login & Terverifikasi)
Route::middleware(['auth', 'verified'])->group(function () {
    
    // Dasar Dashboard Bawaan
    Route::get('dashboard', function () {
        return Inertia::render('Dashboard');
    })->name('dashboard');

    // Fase 3: Customer Service & Modul Sales System API
    Route::resource('complaints', ComplaintController::class);
    Route::resource('bad-reviews', BadReviewController::class);
    Route::resource('order-trackings', OrderTrackingController::class);
    Route::resource('oos', OosController::class);
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
