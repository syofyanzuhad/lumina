<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();

        // Always a Collection (never a bare array) so callers get a consistent
        // API regardless of whether the user is authenticated.
        $sites = $user ? $user->sites()->select('id', 'domain')->get() : collect();
        $activeSiteId = null;

        if ($user && $sites->isNotEmpty()) {
            $requestedSiteId = $request->query('site_id');
            $activeSite = $requestedSiteId ? $sites->firstWhere('id', (int) $requestedSiteId) : null;
            $activeSiteId = $activeSite ? $activeSite->id : $sites->first()->id;
        }

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $user,
            ],
            'sites' => $sites,
            'active_site_id' => $activeSiteId,
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
        ];
    }
}
