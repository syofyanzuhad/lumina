<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Http\JsonResponse;
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

        if (! $request->has('site_id')) {
            $defaultSite = $sites->first();

            return redirect()->route('dashboard', array_merge(
                $request->query(),
                ['site_id' => $defaultSite->id]
            ));
        }

        $activeSiteId = $request->query('site_id');

        /** @var Site $activeSite */
        $activeSite = $sites->firstWhere('id', (int) $activeSiteId) ?? $sites->first();

        $period = $request->query('period', '30d');
        [$start, $end] = $this->resolveDateRange($period, $request->query('start_date'), $request->query('end_date'));

        $activeTab = $request->query('tab', 'overview');

        $filters = $request->only([
            'path', 'referrer', 'country', 'browser', 'os', 'device', 'utm_campaign',
        ]);
        $filters = array_filter($filters, fn ($val) => ! is_null($val) && $val !== '');

        $data = [
            'sites' => $sites,
            'activeSite' => $activeSite,
            'period' => $period,
            'activeTab' => $activeTab,
            'filters' => $filters,
        ];

        if ($activeTab === 'overview') {
            $kpis = $analytics->getKpis($activeSite, $start, $end, $filters);

            // Merge KPI props at the top level so they're available immediately.
            $data = array_merge($data, $kpis);

            // Breakdown cards are deferred — they load after the KPIs render.
            $data['top_pages'] = Inertia::defer(fn () => $analytics->getTopPages($activeSite, $start, $end, 50, $filters));
            $data['top_referrers'] = Inertia::defer(fn () => $analytics->getTopReferrers($activeSite, $start, $end, 50, $filters));
            $data['device_breakdown'] = Inertia::defer(fn () => $analytics->getDeviceBreakdown($activeSite, $start, $end, $filters));
            $data['top_browsers'] = Inertia::defer(fn () => $analytics->getTopBrowsers($activeSite, $start, $end, 50, $filters));
            $data['top_os'] = Inertia::defer(fn () => $analytics->getTopOperatingSystems($activeSite, $start, $end, 50, $filters));
            $data['top_countries'] = Inertia::defer(fn () => $analytics->getTopCountries($activeSite, $start, $end, 50, $filters));
            $data['utm_campaigns'] = Inertia::defer(fn () => $analytics->getUtmCampaigns($activeSite, $start, $end, 50, $filters));
            $data['custom_events'] = Inertia::defer(fn () => $analytics->getCustomEvents($activeSite, $start, $end, 50, $filters));
            $data['goals'] = Inertia::defer(fn () => $analytics->getGoals($activeSite, $start, $end, $filters));
        } elseif ($activeTab === 'events') {
            $selectedEvent = is_string($request->query('event')) ? $request->query('event') : null;
            $selectedPropertyKey = is_string($request->query('property')) ? $request->query('property') : null;

            $data['selectedEvent'] = $selectedEvent;
            $data['selectedPropertyKey'] = $selectedPropertyKey;

            $data['custom_event_summary'] = $analytics->getCustomEventSummary($activeSite, $start, $end, $selectedEvent);
            $data['custom_events_list'] = $analytics->getCustomEventsList($activeSite, $start, $end);
            $data['custom_event_timeline'] = $analytics->getCustomEventTimeline($activeSite, $start, $end, $selectedEvent);

            if ($selectedEvent) {
                $data['custom_event_property_keys'] = $analytics->getCustomEventPropertyKeys($activeSite, $selectedEvent, $start, $end);

                if ($selectedPropertyKey) {
                    $data['custom_event_property_breakdown'] = $analytics->getCustomEventPropertyBreakdown($activeSite, $selectedEvent, $selectedPropertyKey, $start, $end);
                } else {
                    $data['custom_event_property_breakdown'] = [];
                }
            } else {
                $data['custom_event_property_keys'] = [];
                $data['custom_event_property_breakdown'] = [];
            }

            $data['custom_event_logs'] = $analytics->getCustomEventLogs($activeSite, $start, $end, $selectedEvent);
        }

        return Inertia::render('Dashboard', $data);
    }

    public function breakdown(Request $request, AnalyticsService $analytics): JsonResponse
    {
        $user = $request->user();
        $siteId = (int) $request->query('site_id');
        $site = Site::where('owner_id', $user->id)->findOrFail($siteId);

        $period = $request->query('period', '30d');
        [$start, $end] = $this->resolveDateRange($period, $request->query('start_date'), $request->query('end_date'));

        $type = $request->query('type');
        $limit = (int) $request->query('limit', 50);

        $filters = $request->only([
            'path', 'referrer', 'country', 'browser', 'os', 'device', 'utm_campaign',
        ]);
        $filters = array_filter($filters, fn ($val) => ! is_null($val) && $val !== '');

        $data = match ($type) {
            'pages' => $analytics->getTopPages($site, $start, $end, $limit, $filters),
            'referrers' => $analytics->getTopReferrers($site, $start, $end, $limit, $filters),
            'browsers' => $analytics->getTopBrowsers($site, $start, $end, $limit, $filters),
            'os' => $analytics->getTopOperatingSystems($site, $start, $end, $limit, $filters),
            'locations' => $analytics->getTopCountries($site, $start, $end, $limit, $filters),
            'utm' => $analytics->getUtmCampaigns($site, $start, $end, $limit, $filters),
            'devices' => $analytics->getDeviceBreakdown($site, $start, $end, $filters),
            default => [],
        };

        return response()->json([
            'type' => $type,
            'data' => $data,
        ]);
    }

    /**
     * @return array{CarbonInterface, CarbonInterface}
     */
    protected function resolveDateRange(string $period, ?string $startDate, ?string $endDate): array
    {
        if ($period === 'today') {
            return [
                now()->startOfDay(),
                now()->endOfDay(),
            ];
        }

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
