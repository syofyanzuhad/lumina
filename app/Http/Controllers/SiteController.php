<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSiteRequest;
use App\Models\Site;
use Illuminate\Http\RedirectResponse;

class SiteController extends Controller
{
    public function store(StoreSiteRequest $request): RedirectResponse
    {
        $site = $request->user()->sites()->create($request->validated());

        return redirect()->route('sites.show', $site);
    }

    public function show(Site $site)
    {
        return response('');
    }
}
