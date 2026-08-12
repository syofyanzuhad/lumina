<?php

namespace Lumina\Core;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;
use Lumina\Core\Livewire\Dashboard;
use Lumina\Core\Middleware\TrackPageview;

class LuminaCoreServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'lumina');

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/lumina.php' => config_path('lumina.php'),
            ], 'lumina-core-config');

            $this->publishes([
                __DIR__.'/../database/migrations' => database_path('migrations'),
            ], 'lumina-core-migrations');

            $this->publishes([
                __DIR__.'/../resources/views' => resource_path('views/vendor/lumina'),
            ], 'lumina-core-views');
        }

        $this->configureRateLimiting();
        $this->registerMiddlewareAlias();
        $this->registerLivewireComponents();
    }

    protected function registerLivewireComponents(): void
    {
        if (class_exists(Livewire::class)) {
            Livewire::component('lumina-dashboard', Dashboard::class);
        }
    }

    protected function configureRateLimiting(): void
    {
        RateLimiter::for('lumina_ip', function (Request $request) {
            return Limit::perMinute(60)->by($request->ip());
        });

        RateLimiter::for('lumina_site', function (Request $request) {
            return Limit::perMinute(300)->by('site:'.$request->getHost());
        });
    }

    protected function registerMiddlewareAlias(): void
    {
        $this->app->make(Router::class)->aliasMiddleware('lumina.track', TrackPageview::class);
    }

    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/lumina.php', 'lumina');
        $this->registerMiddlewareAlias();
    }
}
