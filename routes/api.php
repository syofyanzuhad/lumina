<?php

use Illuminate\Support\Facades\Route;
use Lumina\Core\Http\Controllers\CollectController;

Route::post('/collect', CollectController::class)
    ->middleware(['throttle:lumina_ip', 'throttle:lumina_site']);
