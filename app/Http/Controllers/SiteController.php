<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSiteRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Lumina\Core\Models\Site;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SiteController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('Sites/Index', [
            'sites' => $request->user()->sites()->orderBy('created_at', 'desc')->get(),
        ]);
    }

    public function create(): Response
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

    public function show(Site $site): Response
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

    public function export(Site $site): StreamedResponse
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

            if ($file === false) {
                return;
            }

            fputcsv($file, ['ID', 'Path', 'Referrer', 'Device Type', 'Created At']);

            $site->events()->chunk(1000, function ($events) use ($file) {
                foreach ($events as $event) {
                    fputcsv($file, [
                        $event->id,
                        $event->path,
                        $event->referrer,
                        is_object($event->device_type) ? $event->device_type->value : (string) $event->device_type,
                        $event->created_at->toDateTimeString(),
                    ]);
                }
            });

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
