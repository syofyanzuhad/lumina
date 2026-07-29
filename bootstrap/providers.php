<?php

use App\Providers\AppServiceProvider;
use App\Providers\FortifyServiceProvider;
use Lumina\Core\LuminaCoreServiceProvider;

return [
    AppServiceProvider::class,
    FortifyServiceProvider::class,
    LuminaCoreServiceProvider::class,
];
