<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Lumina\Core\Models\Site;

class ActiveSiteController extends Controller
{
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'site_id' => [
                'required',
                'integer',
                Rule::exists('sites', 'id')->where('owner_id', $request->user()->id),
            ],
        ]);

        /** @var Site $site */
        $site = $request->user()->sites()->findOrFail($validated['site_id']);

        session()->put('active_site_id', $site->id);

        return back();
    }
}
