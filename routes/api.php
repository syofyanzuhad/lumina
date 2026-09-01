<?php

use App\Http\Controllers\Api\V1\StatsController;
use Illuminate\Support\Facades\Route;
use Lumina\Core\Http\Controllers\CollectController;

Route::match(['get', 'post', 'options'], '/collect', CollectController::class);

Route::get('/v1/stats', [StatsController::class, 'index'])->name('api.v1.stats');
