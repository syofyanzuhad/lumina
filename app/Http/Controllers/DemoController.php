<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Lumina\Core\Models\Site;
use Lumina\Core\Services\AnalyticsService;

class DemoController extends Controller
{
    public function index(Request $request, AnalyticsService $analytics): Response
    {
        $site = Site::where('share_token', 'demo-share-token-analytics')->first()
            ?? Site::where('is_public', true)->first()
            ?? Site::first();

        if ($site && $site->share_token && $site->is_public) {
            return app(ShareController::class)->show($request, $site->share_token, $analytics);
        }

        if (! $site) {
            $site = new Site([
                'id' => 1,
                'domain' => 'demo.lumina.dev',
            ]);
        }

        $period = $request->query('period', '30d');
        [$start, $end] = $this->resolveDateRange($period);

        if ($site->exists) {
            // Real site data: use deferred props for breakdown cards
            $kpis = $analytics->getKpis($site, $start, $end);

            return Inertia::render('Share/Show', array_merge([
                'site' => [
                    'id' => $site->id,
                    'domain' => $site->domain,
                    'is_public' => true,
                    'share_token' => $site->share_token ?? 'demo',
                    'has_password' => false,
                ],
                'requiresPassword' => false,
                'period' => $period,
                'activeTab' => $request->query('tab', 'overview'),
            ], $kpis, [
                'top_pages' => Inertia::defer(fn () => $analytics->getTopPages($site, $start, $end, 50)),
                'top_referrers' => Inertia::defer(fn () => $analytics->getTopReferrers($site, $start, $end, 50)),
                'device_breakdown' => Inertia::defer(fn () => $analytics->getDeviceBreakdown($site, $start, $end)),
                'top_browsers' => Inertia::defer(fn () => $analytics->getTopBrowsers($site, $start, $end, 50)),
                'top_os' => Inertia::defer(fn () => $analytics->getTopOperatingSystems($site, $start, $end, 50)),
                'top_countries' => Inertia::defer(fn () => $analytics->getTopCountries($site, $start, $end, 50)),
                'utm_campaigns' => Inertia::defer(fn () => $analytics->getUtmCampaigns($site, $start, $end, 50)),
                'custom_events' => Inertia::defer(fn () => $analytics->getCustomEvents($site, $start, $end, 50)),
                'goals' => Inertia::defer(fn () => $analytics->getGoals($site, $start, $end)),
            ]));
        }

        // Static fallback demo data (no real site exists)
        return Inertia::render('Share/Show', [
            'site' => [
                'id' => $site->id,
                'domain' => $site->domain,
                'is_public' => true,
                'share_token' => $site->share_token ?? 'demo',
                'has_password' => false,
            ],
            'requiresPassword' => false,
            'period' => $period,
            'activeTab' => $request->query('tab', 'overview'),
            'total_pageviews' => 18450,
            'unique_visitors' => 6120,
            'current_visitors' => 0,
            'bounce_rate' => 42.5,
            'avg_duration' => 127,
            'daily_pageviews' => [
                ['date' => '2026-07-24', 'pageviews' => 410, 'visitors' => 150],
                ['date' => '2026-07-25', 'pageviews' => 520, 'visitors' => 210],
                ['date' => '2026-07-26', 'pageviews' => 890, 'visitors' => 340],
                ['date' => '2026-07-27', 'pageviews' => 1250, 'visitors' => 510],
                ['date' => '2026-07-28', 'pageviews' => 980, 'visitors' => 400],
                ['date' => '2026-07-29', 'pageviews' => 1100, 'visitors' => 460],
                ['date' => '2026-07-30', 'pageviews' => 1340, 'visitors' => 580],
            ],
            'top_pages' => [
                ['path' => '/', 'count' => 5420, 'percentage' => 29.4],
                ['path' => '/signup', 'count' => 2835, 'percentage' => 15.4],
                ['path' => '/pricing', 'count' => 2316, 'percentage' => 12.6],
                ['path' => '/blog/laravel-12-analytics', 'count' => 2100, 'percentage' => 11.4],
                ['path' => '/docs', 'count' => 1830, 'percentage' => 9.9],
                ['path' => '/features', 'count' => 1145, 'percentage' => 6.2],
                ['path' => '/login', 'count' => 934, 'percentage' => 5.1],
                ['path' => '/integrations', 'count' => 719, 'percentage' => 3.9],
                ['path' => '/docs/installation', 'count' => 516, 'percentage' => 2.8],
                ['path' => '/settings', 'count' => 405, 'percentage' => 2.2],
            ],
            'top_referrers' => [
                ['referrer' => 'google.com', 'count' => 6100, 'percentage' => 33.1],
                ['referrer' => 'x.com', 'count' => 3200, 'percentage' => 17.3],
                ['referrer' => 'github.com', 'count' => 2950, 'percentage' => 16.0],
                ['referrer' => 'news.ycombinator.com', 'count' => 2200, 'percentage' => 11.9],
                ['referrer' => 'producthunt.com', 'count' => 1400, 'percentage' => 7.6],
                ['referrer' => 'reddit.com', 'count' => 980, 'percentage' => 5.3],
                ['referrer' => 'dev.to', 'count' => 620, 'percentage' => 3.4],
                ['referrer' => 'youtube.com', 'count' => 450, 'percentage' => 2.4],
                ['referrer' => 'medium.com', 'count' => 290, 'percentage' => 1.6],
                ['referrer' => 'bing.com', 'count' => 180, 'percentage' => 1.0],
            ],
            'device_breakdown' => [
                ['device' => 'desktop', 'count' => 11070, 'percentage' => 60.0],
                ['device' => 'mobile', 'count' => 6457, 'percentage' => 35.0],
                ['device' => 'tablet', 'count' => 923, 'percentage' => 5.0],
            ],
            'top_browsers' => [],
            'top_os' => [],
            'top_countries' => [],
            'utm_campaigns' => [],
            'custom_events' => [
                ['name' => 'newsletter_signup', 'count' => 342],
                ['name' => 'copy_tracking_code', 'count' => 189],
            ],
            'goals' => [],
        ]);

    }

    protected function resolveDateRange(string $period): array
    {
        if ($period === '7d') {
            return [
                now()->subDays(6)->startOfDay(),
                now()->endOfDay(),
            ];
        }

        return [
            now()->subDays(29)->startOfDay(),
            now()->endOfDay(),
        ];
    }
}
