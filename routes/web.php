<?php

use App\Http\Controllers\ActiveSiteController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DemoController;
use App\Http\Controllers\ExportController;
use App\Http\Controllers\GoalController;
use App\Http\Controllers\SiteController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home')->middleware('lumina.track');
Route::get('/demo', [DemoController::class, 'index'])->name('demo')->middleware('lumina.track');

Route::middleware(['auth', 'verified', 'lumina.track'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/sites', [SiteController::class, 'index'])->name('sites.index');
    Route::get('/sites/create', [SiteController::class, 'create'])->name('sites.create');
    Route::post('/sites', [SiteController::class, 'store'])->name('sites.store');
    Route::put('/sites/active', [ActiveSiteController::class, 'update'])->name('active-site.update');
    Route::get('/sites/{site}', [SiteController::class, 'show'])->name('sites.show');
    Route::get('/sites/{site}/export', [ExportController::class, 'export'])->name('sites.export');
    Route::delete('/sites/{site}', [SiteController::class, 'destroy'])->name('sites.destroy');

    // Goals
    Route::get('/sites/{site}/goals', [GoalController::class, 'index'])->name('sites.goals.index');
    Route::post('/sites/{site}/goals', [GoalController::class, 'store'])->name('sites.goals.store');
    Route::put('/sites/{site}/goals/{goal}', [GoalController::class, 'update'])->name('sites.goals.update');
    Route::delete('/sites/{site}/goals/{goal}', [GoalController::class, 'destroy'])->name('sites.goals.destroy');
});

require __DIR__.'/settings.php';
