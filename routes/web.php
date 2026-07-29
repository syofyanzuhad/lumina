<?php

use App\Http\Controllers\ActiveSiteController;
use App\Http\Controllers\SiteController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home')->middleware('lumina.track');

Route::middleware(['auth', 'verified', 'lumina.track'])->group(function () {
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');
    Route::get('/sites', [SiteController::class, 'index'])->name('sites.index');
    Route::get('/sites/create', [SiteController::class, 'create'])->name('sites.create');
    Route::post('/sites', [SiteController::class, 'store'])->name('sites.store');
    Route::put('/sites/active', [ActiveSiteController::class, 'update'])->name('active-site.update');
    Route::get('/sites/{site}', [SiteController::class, 'show'])->name('sites.show');
    Route::delete('/sites/{site}', [SiteController::class, 'destroy'])->name('sites.destroy');
});

require __DIR__.'/settings.php';
