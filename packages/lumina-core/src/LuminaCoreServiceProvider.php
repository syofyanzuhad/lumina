<?php

namespace Lumina\Core;

use Illuminate\Support\ServiceProvider;

class LuminaCoreServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../database/migrations' => database_path('migrations'),
            ], 'lumina-core-migrations');
        }
    }

    public function register(): void
    {
        //
    }
}
