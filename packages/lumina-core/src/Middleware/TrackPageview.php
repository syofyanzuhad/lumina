<?php

namespace Lumina\Core\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Lumina\Core\Enums\DeviceType;
use Lumina\Core\Jobs\InsertEvent;
use Lumina\Core\Models\Site;
use Symfony\Component\HttpFoundation\Response;

class TrackPageview
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $host = $request->getHost();

        // 1. Resolve site by domain
        $site = Site::where('domain', $host)->first();

        if (! $site) {
            return $next($request);
        }

        // 2. Check IP rate limiter (silent swallow)
        $ipKey = 'lumina_ip:'.$request->ip();
        if (RateLimiter::tooManyAttempts($ipKey, 60)) {
            return $next($request);
        }

        // 3. Check site rate limiter (silent swallow)
        $siteKey = 'lumina_site:'.$host;
        if (RateLimiter::tooManyAttempts($siteKey, 300)) {
            return $next($request);
        }

        // Hit limiters
        RateLimiter::hit($ipKey, 60);
        RateLimiter::hit($siteKey, 300);

        // 4. Calculate visitor hash with daily salt
        $dailySalt = Cache::remember(
            'lumina_daily_salt_'.now()->format('Y-m-d'),
            86400,
            fn () => Str::random(32)
        );

        $userAgent = $request->userAgent() ?? '';
        $visitorHash = hash('sha256', $request->ip().$userAgent.$dailySalt);

        // 5. Parse device type and country
        $deviceType = DeviceType::fromUserAgent($userAgent);

        $country = $request->header('X-Country')
            ?? $request->header('CF-IPCountry')
            ?? $request->header('X-Vercel-IP-Country');

        $path = '/'.ltrim($request->path(), '/');

        // 6. Dispatch tracking job
        InsertEvent::dispatch(
            siteId: $site->id,
            path: $path,
            referrer: $request->header('referer'),
            visitorHash: $visitorHash,
            deviceType: $deviceType,
            country: $country,
            metadata: null,
            userAgent: $request->userAgent(),
            ip: $request->ip(),
        );

        return $next($request);
    }
}
