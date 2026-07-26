<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ActiveSiteController extends Controller
{
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'site_id' => [
                'required',
                'integer',
                \Illuminate\Validation\Rule::exists('sites', 'id')->where('owner_id', $request->user()->id),
            ],
        ]);

        $site = $request->user()->sites()->findOrFail($validated['site_id']);

        session()->put('active_site_id', $site->id);

        return back();
    }
}
