<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;
use Lumina\Core\Models\Site;
use Lumina\Core\Services\AnalyticsService;

class ShareController extends Controller
{
    /**
     * Display the public shared dashboard.
     */
    public function show(Request $request, string $token, AnalyticsService $analytics): Response
    {
        $site = Site::where('share_token', $token)
            ->where('is_public', true)
            ->firstOrFail();

        if ($site->hasSharePassword() && ! session("share_auth_{$site->id}")) {
            return Inertia::render('Share/Show', [
                'site' => [
                    'id' => $site->id,
                    'domain' => $site->domain,
                ],
                'requiresPassword' => true,
                'passwordError' => session('errors') ? session('errors')->first('password') : null,
            ]);
        }

        $period = $request->query('period', '30d');
        [$start, $end] = $this->resolveDateRange($period, $request->query('start_date'), $request->query('end_date'));
        $activeTab = $request->query('tab', 'overview');

        $filters = $request->only([
            'path', 'referrer', 'country', 'browser', 'os', 'device', 'utm_campaign',
        ]);
        $filters = array_filter($filters, fn ($val) => ! is_null($val) && $val !== '');

        $data = [
            'site' => [
                'id' => $site->id,
                'domain' => $site->domain,
                'is_public' => $site->is_public,
                'share_token' => $site->share_token,
                'has_password' => $site->hasSharePassword(),
            ],
            'requiresPassword' => false,
            'period' => $period,
            'activeTab' => $activeTab,
            'filters' => $filters,
        ];

        if ($activeTab === 'overview') {
            $kpis = $analytics->getKpis($site, $start, $end, $filters);

            // Merge KPI props at the top level so they're available immediately.
            $data = array_merge($data, $kpis);

            // Breakdown cards are deferred — they load after the KPIs render.
            $data['top_pages'] = Inertia::defer(fn () => $analytics->getTopPages($site, $start, $end, 50, $filters));
            $data['top_referrers'] = Inertia::defer(fn () => $analytics->getTopReferrers($site, $start, $end, 50, $filters));
            $data['device_breakdown'] = Inertia::defer(fn () => $analytics->getDeviceBreakdown($site, $start, $end, $filters));
            $data['top_browsers'] = Inertia::defer(fn () => $analytics->getTopBrowsers($site, $start, $end, 50, $filters));
            $data['top_os'] = Inertia::defer(fn () => $analytics->getTopOperatingSystems($site, $start, $end, 50, $filters));
            $data['top_countries'] = Inertia::defer(fn () => $analytics->getTopCountries($site, $start, $end, 50, $filters));
            $data['utm_campaigns'] = Inertia::defer(fn () => $analytics->getUtmCampaigns($site, $start, $end, 50, $filters));
            $data['custom_events'] = Inertia::defer(fn () => $analytics->getCustomEvents($site, $start, $end, 50, $filters));
            $data['goals'] = Inertia::defer(fn () => $analytics->getGoals($site, $start, $end, $filters));
        } elseif ($activeTab === 'events') {
            $selectedEvent = $request->query('event');
            $selectedPropertyKey = $request->query('property');

            $data['selectedEvent'] = $selectedEvent;
            $data['selectedPropertyKey'] = $selectedPropertyKey;

            $data['custom_event_summary'] = $analytics->getCustomEventSummary($site, $start, $end, $selectedEvent);
            $data['custom_events_list'] = $analytics->getCustomEventsList($site, $start, $end);
            $data['custom_event_timeline'] = $analytics->getCustomEventTimeline($site, $start, $end, $selectedEvent);

            if ($selectedEvent) {
                $data['custom_event_property_keys'] = $analytics->getCustomEventPropertyKeys($site, $selectedEvent, $start, $end);

                if ($selectedPropertyKey) {
                    $data['custom_event_property_breakdown'] = $analytics->getCustomEventPropertyBreakdown($site, $selectedEvent, $selectedPropertyKey, $start, $end);
                } else {
                    $data['custom_event_property_breakdown'] = [];
                }
            } else {
                $data['custom_event_property_keys'] = [];
                $data['custom_event_property_breakdown'] = [];
            }

            $data['custom_event_logs'] = $analytics->getCustomEventLogs($site, $start, $end, $selectedEvent);
        }

        return Inertia::render('Share/Show', $data);
    }

    /**
     * Get detailed breakdown items for side modal.
     */
    public function breakdown(Request $request, string $token, AnalyticsService $analytics)
    {
        $site = Site::where('share_token', $token)
            ->where('is_public', true)
            ->firstOrFail();

        if ($site->hasSharePassword() && ! session("share_auth_{$site->id}")) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

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
     * Authenticate visitor with password for shared site.
     */
    public function authenticate(Request $request, string $token): RedirectResponse
    {
        $site = Site::where('share_token', $token)
            ->where('is_public', true)
            ->firstOrFail();

        $request->validate([
            'password' => ['required', 'string'],
        ]);

        if (Hash::check($request->password, $site->share_password)) {
            session(["share_auth_{$site->id}" => true]);

            return redirect()->route('sites.share.show', $token);
        }

        return back()->withErrors([
            'password' => 'Incorrect password. Please try again.',
        ]);
    }

    /**
     * Update public share settings for a site.
     */
    public function update(Request $request, Site $site): RedirectResponse
    {
        Gate::authorize('update', $site);

        $validated = $request->validate([
            'is_public' => ['required', 'boolean'],
            'share_password' => ['nullable', 'string', 'min:4'],
            'clear_password' => ['nullable', 'boolean'],
        ]);

        $site->is_public = $validated['is_public'];

        if ($site->is_public && empty($site->share_token)) {
            $site->share_token = $site->generateShareToken();
        }

        if (! empty($validated['clear_password'])) {
            $site->share_password = null;
        } elseif ($request->has('share_password')) {
            if (filled($validated['share_password'])) {
                $site->share_password = Hash::make($validated['share_password']);
            }
        }

        $site->save();

        return back()->with('status', 'Share settings updated successfully.');
    }

    /**
     * Regenerate share token for a site.
     */
    public function regenerate(Request $request, Site $site): RedirectResponse
    {
        Gate::authorize('update', $site);

        $site->share_token = $site->generateShareToken();
        $site->save();

        return back()->with('status', 'Share link regenerated successfully.');
    }

    /**
     * Resolve start and end dates from period string.
     */
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
