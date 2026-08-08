<?php

use App\Http\Controllers\ActiveSiteController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DemoController;
use App\Http\Controllers\ExportController;
use App\Http\Controllers\GoalController;
use App\Http\Controllers\ShareController;
use App\Http\Controllers\SiteController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home')->middleware('lumina.track');
Route::get('/demo', [DemoController::class, 'index'])->name('demo')->middleware('lumina.track');

// Public Share Routes
Route::middleware(['lumina.track'])->group(function () {
    Route::get('/share/{token}', [ShareController::class, 'show'])->name('sites.share.show');
    Route::post('/share/{token}/password', [ShareController::class, 'authenticate'])->name('sites.share.authenticate');
});

Route::middleware(['auth', 'verified', 'lumina.track'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/breakdown', [DashboardController::class, 'breakdown'])->name('dashboard.breakdown');
    Route::get('/sites', [SiteController::class, 'index'])->name('sites.index');
    Route::get('/sites/create', [SiteController::class, 'create'])->name('sites.create');
    Route::post('/sites', [SiteController::class, 'store'])->name('sites.store');
    Route::put('/sites/active', [ActiveSiteController::class, 'update'])->name('active-site.update');
    Route::get('/sites/{site}', [SiteController::class, 'show'])->name('sites.show');
    Route::get('/sites/{site}/export', [ExportController::class, 'export'])->name('sites.export');
    Route::delete('/sites/{site}', [SiteController::class, 'destroy'])->name('sites.destroy');

    // Public Share Management
    Route::put('/sites/{site}/share', [ShareController::class, 'update'])->name('sites.share.update');
    Route::post('/sites/{site}/share/regenerate', [ShareController::class, 'regenerate'])->name('sites.share.regenerate');

    // Goals
    Route::get('/sites/{site}/goals', [GoalController::class, 'index'])->name('sites.goals.index');
    Route::post('/sites/{site}/goals', [GoalController::class, 'store'])->name('sites.goals.store');
    Route::put('/sites/{site}/goals/{goal}', [GoalController::class, 'update'])->name('sites.goals.update');
    Route::delete('/sites/{site}/goals/{goal}', [GoalController::class, 'destroy'])->name('sites.goals.destroy');
});

require __DIR__.'/settings.php';
