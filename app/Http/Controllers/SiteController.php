<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSiteRequest;
use App\Models\Site;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

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

        return redirect()->route('sites.show', $site);
    }

    public function show(Site $site)
    {
        Gate::authorize('view', $site);

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
}
