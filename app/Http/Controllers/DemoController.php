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

        $overview = $site->exists
            ? $analytics->getOverview($site, $start, $end)
            : [
                'total_pageviews' => 12450,
                'unique_visitors' => 4120,
                'top_pages' => [
                    ['path' => '/', 'count' => 5420, 'percentage' => 43.5],
                    ['path' => '/blog/laravel-12-analytics', 'count' => 3100, 'percentage' => 24.9],
                    ['path' => '/pricing', 'count' => 2100, 'percentage' => 16.8],
                    ['path' => '/docs', 'count' => 1830, 'percentage' => 14.7],
                ],
                'top_referrers' => [
                    ['referrer' => 'google.com', 'count' => 6100, 'percentage' => 49.0],
                    ['referrer' => 'x.com', 'count' => 3200, 'percentage' => 25.7],
                    ['referrer' => 'github.com', 'count' => 1950, 'percentage' => 15.6],
                    ['referrer' => 'news.ycombinator.com', 'count' => 1200, 'percentage' => 9.6],
                ],
                'daily_pageviews' => [
                    ['date' => '2026-07-24', 'pageviews' => 410, 'visitors' => 150],
                    ['date' => '2026-07-25', 'pageviews' => 520, 'visitors' => 210],
                    ['date' => '2026-07-26', 'pageviews' => 890, 'visitors' => 340],
                    ['date' => '2026-07-27', 'pageviews' => 1250, 'visitors' => 510],
                    ['date' => '2026-07-28', 'pageviews' => 980, 'visitors' => 400],
                    ['date' => '2026-07-29', 'pageviews' => 1100, 'visitors' => 460],
                    ['date' => '2026-07-30', 'pageviews' => 1340, 'visitors' => 580],
                ],
                'device_breakdown' => [
                    ['device' => 'desktop', 'count' => 7470, 'percentage' => 60.0],
                    ['device' => 'mobile', 'count' => 4357, 'percentage' => 35.0],
                    ['device' => 'tablet', 'count' => 623, 'percentage' => 5.0],
                ],
                'custom_events' => [
                    ['name' => 'newsletter_signup', 'count' => 342],
                    ['name' => 'copy_tracking_code', 'count' => 189],
                ],
            ];

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
            'overview' => $overview,
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
