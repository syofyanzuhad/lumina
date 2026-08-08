<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSiteRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Lumina\Core\Models\Site;

class SiteController extends Controller
{
    public function index(Request $request)
    {
        return Inertia::render('Sites/Index', [
            'sites' => $request->user()->sites()->orderBy('created_at', 'desc')->get(),
        ]);
    }

    public function create()
    {
        return Inertia::render('Sites/Create');
    }

    public function store(StoreSiteRequest $request): RedirectResponse
    {
        $site = $request->user()->sites()->create($request->validated());
        if (! $site->api_token) {
            $site->update(['api_token' => $site->generateApiToken()]);
        }

        return redirect()->route('sites.show', $site);
    }

    public function show(Site $site)
    {
        Gate::authorize('view', $site);

        if (! $site->api_token) {
            $site->update(['api_token' => $site->generateApiToken()]);
        }

        return Inertia::render('Sites/Show', [
            'site' => $site,
        ]);
    }

    public function destroy(Site $site): RedirectResponse
    {
        Gate::authorize('delete', $site);

        $site->delete();

        return redirect()->route('sites.index');
    }

    public function export(Site $site)
    {
        Gate::authorize('view', $site);

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="'.$site->domain.'-events.csv"',
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        $callback = function () use ($site) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['ID', 'Path', 'Referrer', 'Device Type', 'Created At']);

            $site->events()->chunk(1000, function ($events) use ($file) {
                foreach ($events as $event) {
                    fputcsv($file, [
                        $event->id,
                        $event->path,
                        $event->referrer,
                        $event->device_type->value ?? 'unknown',
                        $event->created_at->toDateTimeString(),
                    ]);
                }
            });

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
