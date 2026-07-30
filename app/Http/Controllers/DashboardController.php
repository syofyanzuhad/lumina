<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Lumina\Core\Models\Site;
use Lumina\Core\Services\AnalyticsService;

class DashboardController extends Controller
{
    public function index(Request $request, AnalyticsService $analytics): Response|RedirectResponse
    {
        $user = $request->user();
        $sites = Site::where('owner_id', $user->id)->orderBy('domain')->get(['id', 'domain']);

        if ($sites->isEmpty()) {
            return redirect()->route('sites.create');
        }

        $activeSiteId = $request->query('site_id') ?? session('active_site_id');
        $activeSite = $sites->firstWhere('id', (int) $activeSiteId);

        if (! $activeSite) {
            $activeSite = $sites->first();
        }

        session(['active_site_id' => $activeSite->id]);

        $period = $request->query('period', '30d');
        [$start, $end] = $this->resolveDateRange($period, $request->query('start_date'), $request->query('end_date'));

        $overview = $analytics->getOverview($activeSite, $start, $end);

        return Inertia::render('Dashboard', [
            'sites' => $sites,
            'activeSite' => $activeSite,
            'period' => $period,
            'overview' => $overview,
        ]);
    }

    protected function resolveDateRange(string $period, ?string $startDate, ?string $endDate): array
    {
        if ($period === '7d') {
            return [
                now()->subDays(6)->startOfDay(),
                now()->endOfDay(),
            ];
        }

        if ($period === 'custom' && $startDate && $endDate) {
            return [
                Carbon::parse($startDate)->startOfDay(),
                Carbon::parse($endDate)->endOfDay(),
            ];
        }

        return [
            now()->subDays(29)->startOfDay(),
            now()->endOfDay(),
        ];
    }
}
