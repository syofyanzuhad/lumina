<?php

use App\Http\Controllers\ActiveSiteController;
use App\Http\Controllers\SiteController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');
    Route::post('/sites', [SiteController::class, 'store'])->name('sites.store');
    Route::put('/sites/active', [ActiveSiteController::class, 'update'])->name('active-site.update');
    Route::get('/sites/{site}', [SiteController::class, 'show'])->name('sites.show');
});

require __DIR__.'/settings.php';
