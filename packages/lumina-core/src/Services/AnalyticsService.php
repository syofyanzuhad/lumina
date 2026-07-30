<?php

namespace Lumina\Core\Services;

use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Lumina\Core\Models\Event;
use Lumina\Core\Models\Site;

class AnalyticsService
{
    /**
     * Cache TTL in seconds (default: 60).
     */
    protected int $ttl = 60;

    /**
     * Clear cached analytics metrics for a specific site.
     */
    public function clearCache(Site $site): void
    {
        Cache::forget("lumina:analytics:{$site->id}:pageviews");
        Cache::forget("lumina:analytics:{$site->id}:unique_visitors");
        Cache::forget("lumina:analytics:{$site->id}:daily_pageviews");
        Cache::forget("lumina:analytics:{$site->id}:top_pages_10");
        Cache::forget("lumina:analytics:{$site->id}:top_referrers_10");
        Cache::forget("lumina:analytics:{$site->id}:custom_events_10");
        Cache::forget("lumina:analytics:{$site->id}:device_breakdown");
        Cache::forget("lumina:analytics:{$site->id}:top_browsers_10");
        Cache::forget("lumina:analytics:{$site->id}:top_os_10");
        Cache::forget("lumina:analytics:{$site->id}:top_countries_10");
    }

    /**
     * Get total pageviews for site and date range.
     */
    public function getPageviews(Site $site, CarbonInterface $start, CarbonInterface $end): int
    {
        $cacheKey = $this->cacheKey($site->id, 'pageviews', $start, $end);

        return (int) Cache::remember($cacheKey, $this->ttl, function () use ($site, $start, $end) {
            return Event::where('site_id', $site->id)
                ->whereBetween('created_at', [$start, $end])
                ->count();
        });
    }

    /**
     * Get unique visitors count for site and date range.
     */
    public function getUniqueVisitors(Site $site, CarbonInterface $start, CarbonInterface $end): int
    {
        $cacheKey = $this->cacheKey($site->id, 'unique_visitors', $start, $end);

        return (int) Cache::remember($cacheKey, $this->ttl, function () use ($site, $start, $end) {
            return Event::where('site_id', $site->id)
                ->whereBetween('created_at', [$start, $end])
                ->distinct('visitor_hash')
                ->count('visitor_hash');
        });
    }

    /**
     * Get top pages for site and date range.
     */
    public function getTopPages(Site $site, CarbonInterface $start, CarbonInterface $end, int $limit = 10): Collection
    {
        $cacheKey = $this->cacheKey($site->id, "top_pages_{$limit}", $start, $end);

        $data = Cache::remember($cacheKey, $this->ttl, function () use ($site, $start, $end, $limit) {
            $totalPageviews = $this->getPageviews($site, $start, $end);

            $results = Event::where('site_id', $site->id)
                ->whereBetween('created_at', [$start, $end])
                ->select('path', DB::raw('count(*) as count'))
                ->groupBy('path')
                ->orderByDesc('count')
                ->orderBy('path')
                ->limit($limit)
                ->get();

            return $results->map(function ($row) use ($totalPageviews) {
                $count = (int) $row->count;

                return [
                    'path' => (string) $row->path,
                    'count' => $count,
                    'percentage' => $totalPageviews > 0 ? round(($count / $totalPageviews) * 100, 1) : 0.0,
                ];
            })->toArray();
        });

        return collect($data ?? []);
    }

    /**
     * Get top referrers for site and date range.
     */
    public function getTopReferrers(Site $site, CarbonInterface $start, CarbonInterface $end, int $limit = 10): Collection
    {
        $cacheKey = $this->cacheKey($site->id, "top_referrers_{$limit}", $start, $end);

        $data = Cache::remember($cacheKey, $this->ttl, function () use ($site, $start, $end, $limit) {
            $totalPageviews = $this->getPageviews($site, $start, $end);

            $results = Event::where('site_id', $site->id)
                ->whereBetween('created_at', [$start, $end])
                ->whereNotNull('referrer')
                ->where('referrer', '!=', '')
                ->select('referrer', DB::raw('count(*) as count'))
                ->groupBy('referrer')
                ->orderByDesc('count')
                ->orderBy('referrer')
                ->limit($limit)
                ->get();

            return $results->map(function ($row) use ($totalPageviews) {
                $count = (int) $row->count;

                return [
                    'referrer' => (string) $row->referrer,
                    'count' => $count,
                    'percentage' => $totalPageviews > 0 ? round(($count / $totalPageviews) * 100, 1) : 0.0,
                ];
            })->toArray();
        });

        return collect($data ?? []);
    }

    /**
     * Get daily pageview timeseries for site and date range.
     */
    public function getDailyPageviews(Site $site, CarbonInterface $start, CarbonInterface $end): Collection
    {
        $cacheKey = $this->cacheKey($site->id, 'daily_pageviews', $start, $end);

        $data = Cache::remember($cacheKey, $this->ttl, function () use ($site, $start, $end) {
            $events = Event::where('site_id', $site->id)
                ->whereBetween('created_at', [$start, $end])
                ->get();

            $grouped = $events->groupBy(fn ($e) => $e->created_at->format('Y-m-d'));

            $series = [];
            $curr = $start->copy()->startOfDay();
            $last = $end->copy()->startOfDay();

            while ($curr->lte($last)) {
                $dateStr = $curr->format('Y-m-d');
                $dayEvents = $grouped->get($dateStr, collect());
                $series[] = [
                    'date' => $dateStr,
                    'pageviews' => $dayEvents->count(),
                    'visitors' => $dayEvents->pluck('visitor_hash')->unique()->count(),
                ];
                $curr = $curr->addDay();
            }

            return $series;
        });

        return collect($data ?? []);
    }

    /**
     * Get device breakdown (desktop, mobile, tablet, etc.).
     */
    public function getDeviceBreakdown(Site $site, CarbonInterface $start, CarbonInterface $end): Collection
    {
        $cacheKey = $this->cacheKey($site->id, 'device_breakdown', $start, $end);

        $data = Cache::remember($cacheKey, $this->ttl, function () use ($site, $start, $end) {
            $totalPageviews = $this->getPageviews($site, $start, $end);

            $results = Event::where('site_id', $site->id)
                ->whereBetween('created_at', [$start, $end])
                ->select('device_type', DB::raw('count(*) as count'))
                ->groupBy('device_type')
                ->orderByDesc('count')
                ->get();

            return $results->map(function ($row) use ($totalPageviews) {
                $count = (int) $row->count;

                return [
                    'device' => is_object($row->device_type) ? $row->device_type->value : (string) $row->device_type,
                    'count' => $count,
                    'percentage' => $totalPageviews > 0 ? round(($count / $totalPageviews) * 100, 1) : 0.0,
                ];
            })->toArray();
        });

        return collect($data ?? []);
    }

    /**
     * Get top browsers for site and date range.
     */
    public function getTopBrowsers(Site $site, CarbonInterface $start, CarbonInterface $end, int $limit = 10): Collection
    {
        $cacheKey = $this->cacheKey($site->id, "top_browsers_{$limit}", $start, $end);

        $data = Cache::remember($cacheKey, $this->ttl, function () use ($site, $start, $end, $limit) {
            $totalPageviews = $this->getPageviews($site, $start, $end);

            $results = Event::where('site_id', $site->id)
                ->whereBetween('created_at', [$start, $end])
                ->whereNotNull('browser')
                ->where('browser', '!=', '')
                ->select('browser', DB::raw('count(*) as count'))
                ->groupBy('browser')
                ->orderByDesc('count')
                ->orderBy('browser')
                ->limit($limit)
                ->get();

            return $results->map(function ($row) use ($totalPageviews) {
                $count = (int) $row->count;

                return [
                    'browser' => (string) $row->browser,
                    'count' => $count,
                    'percentage' => $totalPageviews > 0 ? round(($count / $totalPageviews) * 100, 1) : 0.0,
                ];
            })->toArray();
        });

        return collect($data ?? []);
    }

    /**
     * Get top operating systems for site and date range.
     */
    public function getTopOperatingSystems(Site $site, CarbonInterface $start, CarbonInterface $end, int $limit = 10): Collection
    {
        $cacheKey = $this->cacheKey($site->id, "top_os_{$limit}", $start, $end);

        $data = Cache::remember($cacheKey, $this->ttl, function () use ($site, $start, $end, $limit) {
            $totalPageviews = $this->getPageviews($site, $start, $end);

            $results = Event::where('site_id', $site->id)
                ->whereBetween('created_at', [$start, $end])
                ->whereNotNull('os')
                ->where('os', '!=', '')
                ->select('os', DB::raw('count(*) as count'))
                ->groupBy('os')
                ->orderByDesc('count')
                ->orderBy('os')
                ->limit($limit)
                ->get();

            return $results->map(function ($row) use ($totalPageviews) {
                $count = (int) $row->count;

                return [
                    'os' => (string) $row->os,
                    'count' => $count,
                    'percentage' => $totalPageviews > 0 ? round(($count / $totalPageviews) * 100, 1) : 0.0,
                ];
            })->toArray();
        });

        return collect($data ?? []);
    }

    /**
     * Get top countries for site and date range.
     */
    public function getTopCountries(Site $site, CarbonInterface $start, CarbonInterface $end, int $limit = 10): Collection
    {
        $cacheKey = $this->cacheKey($site->id, "top_countries_{$limit}", $start, $end);

        $data = Cache::remember($cacheKey, $this->ttl, function () use ($site, $start, $end, $limit) {
            $totalPageviews = $this->getPageviews($site, $start, $end);

            $results = Event::where('site_id', $site->id)
                ->whereBetween('created_at', [$start, $end])
                ->where(function ($q) {
                    $q->whereNotNull('country_code')->orWhereNotNull('country');
                })
                ->select(
                    DB::raw('COALESCE(country_code, country) as code'),
                    DB::raw('COALESCE(country_name, country) as name'),
                    DB::raw('count(*) as count')
                )
                ->groupBy('code', 'name')
                ->orderByDesc('count')
                ->limit($limit)
                ->get();

            return $results->map(function ($row) use ($totalPageviews) {
                $count = (int) $row->count;

                return [
                    'code' => (string) $row->code,
                    'name' => (string) $row->name,
                    'count' => $count,
                    'percentage' => $totalPageviews > 0 ? round(($count / $totalPageviews) * 100, 1) : 0.0,
                ];
            })->toArray();
        });

        return collect($data ?? []);
    }

    /**
     * Get custom event breakdown from metadata column.
     */
    public function getCustomEvents(Site $site, CarbonInterface $start, CarbonInterface $end, int $limit = 10): Collection
    {
        $cacheKey = $this->cacheKey($site->id, "custom_events_{$limit}", $start, $end);

        $data = Cache::remember($cacheKey, $this->ttl, function () use ($site, $start, $end, $limit) {
            $events = Event::where('site_id', $site->id)
                ->whereBetween('created_at', [$start, $end])
                ->whereNotNull('metadata')
                ->get();

            return $events
                ->filter(fn ($e) => is_array($e->metadata) && isset($e->metadata['name']))
                ->groupBy(fn ($e) => $e->metadata['name'])
                ->map(fn ($group, $name) => [
                    'name' => (string) $name,
                    'count' => $group->count(),
                ])
                ->sortByDesc('count')
                ->take($limit)
                ->values()
                ->toArray();
        });

        return collect($data ?? []);
    }

    /**
     * Get complete dashboard overview payload.
     */
    public function getOverview(Site $site, CarbonInterface $start, CarbonInterface $end): array
    {
        return [
            'total_pageviews' => $this->getPageviews($site, $start, $end),
            'unique_visitors' => $this->getUniqueVisitors($site, $start, $end),
            'top_pages' => $this->getTopPages($site, $start, $end),
            'top_referrers' => $this->getTopReferrers($site, $start, $end),
            'daily_pageviews' => $this->getDailyPageviews($site, $start, $end),
            'device_breakdown' => $this->getDeviceBreakdown($site, $start, $end),
            'top_browsers' => $this->getTopBrowsers($site, $start, $end),
            'top_os' => $this->getTopOperatingSystems($site, $start, $end),
            'top_countries' => $this->getTopCountries($site, $start, $end),
            'custom_events' => $this->getCustomEvents($site, $start, $end),
        ];
    }

    /**
     * Generate deterministic cache key.
     */
    protected function cacheKey(int $siteId, string $metric, CarbonInterface $start, CarbonInterface $end): string
    {
        $sStr = $start->format('Y-m-d');
        $eStr = $end->format('Y-m-d');

        return "lumina:analytics:{$siteId}:{$metric}:{$sStr}:{$eStr}";
    }
}
