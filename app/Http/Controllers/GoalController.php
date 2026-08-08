<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Lumina\Core\Models\Goal;
use Lumina\Core\Models\Site;

class GoalController extends Controller
{
    public function index(Request $request, Site $site)
    {
        Gate::authorize('view', $site);

        return response()->json($site->goals);
    }

    public function store(Request $request, Site $site)
    {
        Gate::authorize('update', $site);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'target_type' => 'required|in:path,custom_event',
            'target_value' => 'required|string|max:255',
        ]);

        $goal = $site->goals()->create($validated);

        return response()->json($goal, 201);
    }

    public function update(Request $request, Site $site, Goal $goal)
    {
        Gate::authorize('update', $site);

        if ($goal->site_id !== $site->id) {
            abort(404);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'target_type' => 'required|in:path,custom_event',
            'target_value' => 'required|string|max:255',
        ]);

        $goal->update($validated);

        return response()->json($goal);
    }

    public function destroy(Request $request, Site $site, Goal $goal)
    {
        Gate::authorize('update', $site);

        if ($goal->site_id !== $site->id) {
            abort(404);
        }

        $goal->delete();

        return response()->noContent();
    }
}
