<?php

use App\Providers\AdminPanelProvider;
use App\Providers\AppServiceProvider;
use App\Providers\FortifyServiceProvider;
use Lumina\Core\LuminaCoreServiceProvider;

return [
    AdminPanelProvider::class,
    AppServiceProvider::class,
    FortifyServiceProvider::class,
    LuminaCoreServiceProvider::class,
];
