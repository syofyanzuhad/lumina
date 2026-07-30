<?php

use App\Http\Controllers\ActiveSiteController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\SiteController;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\DemoController;

Route::inertia('/', 'Welcome')->name('home')->middleware('lumina.track');
Route::get('/demo', [DemoController::class, 'index'])->name('demo')->middleware('lumina.track');

Route::middleware(['auth', 'verified', 'lumina.track'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/sites', [SiteController::class, 'index'])->name('sites.index');
    Route::get('/sites/create', [SiteController::class, 'create'])->name('sites.create');
    Route::post('/sites', [SiteController::class, 'store'])->name('sites.store');
    Route::put('/sites/active', [ActiveSiteController::class, 'update'])->name('active-site.update');
    Route::get('/sites/{site}', [SiteController::class, 'show'])->name('sites.show');
    Route::get('/sites/{site}/export', [SiteController::class, 'export'])->name('sites.export');
    Route::delete('/sites/{site}', [SiteController::class, 'destroy'])->name('sites.destroy');
    
    // Goals
    Route::get('/sites/{site}/goals', [\App\Http\Controllers\GoalController::class, 'index'])->name('sites.goals.index');
    Route::post('/sites/{site}/goals', [\App\Http\Controllers\GoalController::class, 'store'])->name('sites.goals.store');
    Route::put('/sites/{site}/goals/{goal}', [\App\Http\Controllers\GoalController::class, 'update'])->name('sites.goals.update');
    Route::delete('/sites/{site}/goals/{goal}', [\App\Http\Controllers\GoalController::class, 'destroy'])->name('sites.goals.destroy');
});

require __DIR__.'/settings.php';
