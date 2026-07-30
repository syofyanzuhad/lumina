<?php

use Illuminate\Support\Facades\Route;
use Lumina\Core\Http\Controllers\CollectController;

Route::match(['get', 'post', 'options'], '/collect', CollectController::class)
    ->middleware(['throttle:lumina_ip', 'throttle:lumina_site']);
