<?php

namespace Lumina\Core;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Lumina\Core\Middleware\TrackPageview;

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

        $this->configureRateLimiting();
        $this->registerMiddlewareAlias();
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
        $this->app['router']->aliasMiddleware('lumina.track', TrackPageview::class);
    }

    public function register(): void
    {
        $this->registerMiddlewareAlias();
    }
}
