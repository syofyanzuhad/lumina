<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Carbon\CarbonInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Lumina\Core\Models\Site;
use Lumina\Core\Services\AnalyticsService;
use Lumina\Core\Support\DateRangeHelper;

class StatsController extends Controller
{
    public function index(Request $request, AnalyticsService $analytics): JsonResponse
    {
        $token = $request->header('X-API-Key')
            ?? $request->bearerToken()
            ?? $request->query('api_token');

        if (! $token) {
            return response()->json([
                'error' => 'Unauthorized',
                'message' => 'Missing API token. Provide token via Bearer token, X-API-Key header, or api_token query param.',
            ], 401);
        }

        $site = Site::where('api_token', $token)->first();

        if (! $site) {
            return response()->json([
                'error' => 'Unauthorized',
                'message' => 'Invalid API token.',
            ], 401);
        }

        $period = $request->query('period', '30d');
        [$start, $end] = $this->resolveDateRange($period, $request->query('start_date'), $request->query('end_date'));

        $type = $request->query('type', 'overview');

        if ($type === 'pageviews') {
            return response()->json([
                'site' => $site->domain,
                'period' => $period,
                'total_pageviews' => $analytics->getPageviews($site, $start, $end),
                'unique_visitors' => $analytics->getUniqueVisitors($site, $start, $end),
            ]);
        }

        if ($type === 'top-pages') {
            return response()->json([
                'site' => $site->domain,
                'period' => $period,
                'top_pages' => $analytics->getTopPages($site, $start, $end),
            ]);
        }

        if ($type === 'top-referrers') {
            return response()->json([
                'site' => $site->domain,
                'period' => $period,
                'top_referrers' => $analytics->getTopReferrers($site, $start, $end),
            ]);
        }

        if ($type === 'utm-campaigns') {
            return response()->json([
                'site' => $site->domain,
                'period' => $period,
                'utm_campaigns' => $analytics->getUtmCampaigns($site, $start, $end),
            ]);
        }

        $overview = $analytics->getOverview($site, $start, $end);

        return response()->json([
            'site' => $site->domain,
            'period' => $period,
            'data' => $overview,
        ]);
    }

    /**
     * @return array{CarbonInterface, CarbonInterface}
     */
    protected function resolveDateRange(string $period, ?string $startDate, ?string $endDate): array
    {
        return DateRangeHelper::resolve($period, $startDate, $endDate);
    }
}
