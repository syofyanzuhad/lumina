<?php

namespace Lumina\Core\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Lumina\Core\Enums\DeviceType;
use Lumina\Core\Jobs\InsertEvent;
use Lumina\Core\Models\Site;

class CollectController extends Controller
{
    protected function getCorsHeaders(Request $request): array
    {
        $origin = $request->headers->get('Origin')
            ?? $request->headers->get('origin')
            ?? $request->server->get('HTTP_ORIGIN');

        return [
            'Access-Control-Allow-Origin' => $origin ?: '*',
            'Access-Control-Allow-Methods' => 'GET, POST, OPTIONS',
            'Access-Control-Allow-Headers' => 'Content-Type, X-Requested-With',
            'Access-Control-Allow-Credentials' => 'true',
            'Vary' => 'Origin',
        ];
    }

    public function __invoke(Request $request): JsonResponse
    {
        $corsHeaders = $this->getCorsHeaders($request);

        if ($request->isMethod('OPTIONS')) {
            return response()->json(null, 204, $corsHeaders);
        }

        if ($request->isMethod('GET')) {
            return response()->json([
                'status' => 'ok',
                'message' => 'Lumina Analytics Collector API is active.',
            ], 200, $corsHeaders);
        }

        $validated = $request->validate([
            'domain' => ['required', 'string'],
            'path' => ['required', 'string'],
            'referrer' => ['nullable', 'string'],
            'screen_width' => ['nullable', 'integer'],
            'name' => ['nullable', 'string'],
            'metadata' => ['nullable', 'array'],
        ]);

        $domain = strtolower(trim($validated['domain']));
        $site = Site::where('domain', $domain)->first();

        if (! $site) {
            return response()->json([
                'message' => 'Unregistered domain.',
            ], 422, $corsHeaders);
        }

        $dailySalt = Cache::remember(
            'lumina_daily_salt_'.now()->format('Y-m-d'),
            86400,
            fn () => Str::random(32)
        );

        $userAgent = $request->userAgent() ?? '';
        $visitorHash = hash('sha256', $request->ip().$userAgent.$dailySalt);

        $screenWidth = $request->input('screen_width');
        if ($screenWidth !== null && (int) $screenWidth > 0) {
            $deviceType = DeviceType::fromScreenWidth((int) $screenWidth);
        } else {
            $deviceType = DeviceType::fromUserAgent($userAgent);
        }

        $country = $request->header('X-Country')
            ?? $request->header('CF-IPCountry')
            ?? $request->header('X-Vercel-IP-Country');

        $path = '/'.ltrim($validated['path'], '/');

        $metadata = null;
        if (! empty($validated['name'])) {
            $metadata = [
                'name' => $validated['name'],
                'props' => $validated['metadata'] ?? null,
            ];
        } elseif (! empty($validated['metadata'])) {
            $metadata = $validated['metadata'];
        }

        InsertEvent::dispatch(
            siteId: $site->id,
            path: $path,
            referrer: $validated['referrer'] ?? null,
            visitorHash: $visitorHash,
            deviceType: $deviceType,
            country: $country,
            metadata: $metadata,
        );

        return response()->json(null, 204, $corsHeaders);
    }
}
