<?php

use App\Http\Middleware\HandleAppearance;
use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->encryptCookies(except: ['appearance', 'sidebar_state']);

        // Trust boundary for analytics country headers: only requests from
        // these proxies may supply CF-IPCountry / X-Vercel-IP-Country. Leave
        // TRUSTED_PROXIES empty (safe default) unless the app is deployed
        // directly behind Cloudflare/Vercel, then list their edge IPs (or
        // 'private' for private-network proxies). Never use '*'. See
        // TrackPageview middleware for the deployment-model documentation.
        $middleware->trustProxies(
            at: array_values(array_filter(array_map(
                'trim',
                explode(',', (string) env('TRUSTED_PROXIES', ''))
            ))),
        );

        $middleware->web(append: [
            HandleAppearance::class,
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
