<?php

namespace Lumina\Core\Services;

use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Lumina\Core\Models\Event;
use Lumina\Core\Models\Site;
use Lumina\Core\Support\CountryHelper;
use Lumina\Core\Support\ReferrerHelper;

class AnalyticsService
{
    protected function applyFilters($query, array $filters)
    {
        if (! empty($filters['path'])) {
            if (DB::getDriverName() === 'sqlite') {
                $query->whereRaw("COALESCE(clean_path, CASE WHEN instr(path, '?') > 0 THEN substr(path, 1, instr(path, '?') - 1) ELSE path END) = ?", [$filters['path']]);
            } else {
                $query->whereRaw("COALESCE(clean_path, SUBSTRING_INDEX(path, '?', 1)) = ?", [$filters['path']]);
            }
        }
        if (! empty($filters['referrer'])) {
            $query->where('referrer', $filters['referrer']);
        }
        if (! empty($filters['country'])) {
            $query->where('country_code', $filters['country']);
        }
        if (! empty($filters['browser'])) {
            $query->where('browser', $filters['browser']);
        }
        if (! empty($filters['os'])) {
            $query->where('os', $filters['os']);
        }
        if (! empty($filters['device'])) {
            $query->where('device', $filters['device']);
        }
        if (! empty($filters['utm_campaign'])) {
            $query->where('utm_campaign', $filters['utm_campaign']);
        }
        return $query;
    }

    /**
     * Cache TTL in seconds (default: 60).
     */
    protected int $ttl = 60;

    /**
     * Clear cached analytics metrics for a specific site.
     */
    public function clearCache(Site $site): void
    {
        if (Cache::supportsTags()) {
            Cache::tags(["lumina:site:{$site->id}"])->flush();

            return;
        }

        // For cache drivers without tags support (e.g. file, database default), clear common date ranges/keys
        $commonPeriods = [
            [now()->subDays(6)->startOfDay(), now()->endOfDay()],
            [now()->subDays(29)->startOfDay(), now()->endOfDay()],
            [now()->startOfDay(), now()->endOfDay()],
        ];

        $metrics = [
            'pageviews',
            'unique_visitors',
            'daily_pageviews',
            'top_pages_10',
            'top_referrers_10',
            'custom_events_10',
            'device_breakdown',
            'top_browsers_10',
            'top_os_10',
            'top_countries_10',
            'custom_events_list',
            'custom_event_summary',
            'custom_event_timeline',
            'custom_event_logs',
            'goals',
        ];

        foreach ($commonPeriods as [$start, $end]) {
            foreach ($metrics as $metric) {
                Cache::forget($this->cacheKey($site->id, $metric, $start, $end));
            }
        }
    }

    /**
     * Get total pageviews for site and date range.
     */
    public function getPageviews(Site $site, CarbonInterface $start, CarbonInterface $end, array $filters = []): int
    {
        $cacheKey = $this->cacheKey($site->id, 'pageviews', $start, $end, $filters);

        return (int) $this->rememberCache($site->id, $cacheKey, function () use ($site, $start, $end, $filters) {
            return Event::where('site_id', $site->id)
                ->whereBetween('created_at', [$start, $end])
                ->tap(fn ($q) => $this->applyFilters($q, $filters))
                ->count();
        });
    }

    /**
     * Get unique visitors count for site and date range.
     */
    public function getUniqueVisitors(Site $site, CarbonInterface $start, CarbonInterface $end, array $filters = []): int
    {
        $cacheKey = $this->cacheKey($site->id, 'unique_visitors', $start, $end, $filters);

        return (int) $this->rememberCache($site->id, $cacheKey, function () use ($site, $start, $end, $filters) {
            return Event::where('site_id', $site->id)
                ->whereBetween('created_at', [$start, $end])
                ->tap(fn ($q) => $this->applyFilters($q, $filters))
                ->distinct('visitor_hash')
                ->count('visitor_hash');
        });
    }

    /**
     * Get top pages for site and date range.
     */
    public function getTopPages(Site $site, CarbonInterface $start, CarbonInterface $end, int $limit = 10, array $filters = []): Collection
    {
        $cacheKey = $this->cacheKey($site->id, "top_pages_{$limit}", $start, $end, $filters);

        $data = $this->rememberCache($site->id, $cacheKey, function () use ($site, $start, $end, $limit, $filters) {
            $totalPageviews = $this->getPageviews($site, $start, $end, $filters);

            $pathExpr = DB::getDriverName() === 'sqlite'
                ? DB::raw("COALESCE(clean_path, CASE WHEN instr(path, '?') > 0 THEN substr(path, 1, instr(path, '?') - 1) ELSE path END) as target_path")
                : DB::raw("COALESCE(clean_path, SUBSTRING_INDEX(path, '?', 1)) as target_path");

            $results = Event::where('site_id', $site->id)
                ->whereBetween('created_at', [$start, $end])
                ->tap(fn ($q) => $this->applyFilters($q, $filters))
                ->select($pathExpr, DB::raw('count(*) as count'))
                ->groupBy('target_path')
                ->orderByDesc('count')
                ->orderBy('target_path')
                ->limit($limit)
                ->get();

            return $results->map(function ($row) use ($totalPageviews) {
                $count = (int) $row->count;

                return [
                    'path' => (string) $row->target_path,
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
    public function getTopReferrers(Site $site, CarbonInterface $start, CarbonInterface $end, int $limit = 10, array $filters = []): Collection
    {
        $cacheKey = $this->cacheKey($site->id, "top_referrers_{$limit}", $start, $end, $filters);

        $data = $this->rememberCache($site->id, $cacheKey, function () use ($site, $start, $end, $limit, $filters) {
            $totalPageviews = $this->getPageviews($site, $start, $end, $filters);

            $results = Event::where('site_id', $site->id)
                ->whereBetween('created_at', [$start, $end])
                ->tap(fn ($q) => $this->applyFilters($q, $filters))
                ->whereNotNull('referrer')
                ->where('referrer', '!=', '')
                ->select('referrer', DB::raw('count(*) as count'))
                ->groupBy('referrer')
                ->orderByDesc('count')
                ->get();

            $grouped = $results->groupBy(function ($row) {
                return ReferrerHelper::parseName($row->referrer);
            });

            return $grouped
                ->map(function ($group, $platform) use ($totalPageviews) {
                    $count = $group->sum('count');

                    return [
                        'referrer' => (string) $platform,
                        'count' => $count,
                        'percentage' => $totalPageviews > 0 ? round(($count / $totalPageviews) * 100, 1) : 0.0,
                    ];
                })
                ->sort(function ($a, $b) {
                    if ($a['count'] === $b['count']) {
                        return strcmp($a['referrer'], $b['referrer']);
                    }

                    return $b['count'] <=> $a['count'];
                })
                ->take($limit)
                ->values()
                ->toArray();
        });

        return collect($data ?? []);
    }

    /**
     * Get daily pageview timeseries for site and date range.
     */
    public function getDailyPageviews(Site $site, CarbonInterface $start, CarbonInterface $end, array $filters = []): Collection
    {
        $cacheKey = $this->cacheKey($site->id, 'daily_pageviews', $start, $end, $filters);

        $data = $this->rememberCache($site->id, $cacheKey, function () use ($site, $start, $end, $filters) {
            $dateExpr = DB::getDriverName() === 'sqlite'
                ? DB::raw("strftime('%Y-%m-%d', created_at) as date")
                : DB::raw('DATE(created_at) as date');

            $results = Event::where('site_id', $site->id)
                ->whereBetween('created_at', [$start, $end])
                ->tap(fn ($q) => $this->applyFilters($q, $filters))
                ->select(
                    $dateExpr,
                    DB::raw('count(*) as pageviews'),
                    DB::raw('count(distinct visitor_hash) as visitors')
                )
                ->groupBy('date')
                ->get()
                ->keyBy('date');

            $series = [];
            $curr = $start->copy()->startOfDay();
            $last = $end->copy()->startOfDay();

            while ($curr->lte($last)) {
                $dateStr = $curr->format('Y-m-d');
                $dayRow = $results->get($dateStr);

                $series[] = [
                    'date' => $dateStr,
                    'pageviews' => $dayRow ? (int) $dayRow->pageviews : 0,
                    'visitors' => $dayRow ? (int) $dayRow->visitors : 0,
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
    public function getDeviceBreakdown(Site $site, CarbonInterface $start, CarbonInterface $end, array $filters = []): Collection
    {
        $cacheKey = $this->cacheKey($site->id, 'device_breakdown', $start, $end, $filters);

        $data = $this->rememberCache($site->id, $cacheKey, function () use ($site, $start, $end, $filters) {
            $totalPageviews = $this->getPageviews($site, $start, $end, $filters);

            $results = Event::where('site_id', $site->id)
                ->whereBetween('created_at', [$start, $end])
                ->tap(fn ($q) => $this->applyFilters($q, $filters))
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
    public function getTopBrowsers(Site $site, CarbonInterface $start, CarbonInterface $end, int $limit = 10, array $filters = []): Collection
    {
        $cacheKey = $this->cacheKey($site->id, "top_browsers_{$limit}", $start, $end, $filters);

        $data = $this->rememberCache($site->id, $cacheKey, function () use ($site, $start, $end, $limit, $filters) {
            $totalPageviews = $this->getPageviews($site, $start, $end, $filters);

            $results = Event::where('site_id', $site->id)
                ->whereBetween('created_at', [$start, $end])
                ->tap(fn ($q) => $this->applyFilters($q, $filters))
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
    public function getTopOperatingSystems(Site $site, CarbonInterface $start, CarbonInterface $end, int $limit = 10, array $filters = []): Collection
    {
        $cacheKey = $this->cacheKey($site->id, "top_os_{$limit}", $start, $end, $filters);

        $data = $this->rememberCache($site->id, $cacheKey, function () use ($site, $start, $end, $limit, $filters) {
            $totalPageviews = $this->getPageviews($site, $start, $end, $filters);

            $results = Event::where('site_id', $site->id)
                ->whereBetween('created_at', [$start, $end])
                ->tap(fn ($q) => $this->applyFilters($q, $filters))
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
    public function getTopCountries(Site $site, CarbonInterface $start, CarbonInterface $end, int $limit = 10, array $filters = []): Collection
    {
        $cacheKey = $this->cacheKey($site->id, "top_countries_{$limit}", $start, $end, $filters);

        $data = $this->rememberCache($site->id, $cacheKey, function () use ($site, $start, $end, $limit, $filters) {
            $totalPageviews = $this->getPageviews($site, $start, $end, $filters);

            $countryExpr = DB::raw('UPPER(TRIM(COALESCE(country_code, country))) as code');

            $results = Event::where('site_id', $site->id)
                ->whereBetween('created_at', [$start, $end])
                ->tap(fn ($q) => $this->applyFilters($q, $filters))
                ->where(function ($q) {
                    $q->whereNotNull('country_code')->orWhereNotNull('country');
                })
                ->select($countryExpr, DB::raw('MAX(country_name) as country_name'), DB::raw('count(*) as count'))
                ->groupBy('code')
                ->orderByDesc('count')
                ->get();

            return $results
                ->map(function ($row) use ($totalPageviews) {
                    $code = (string) $row->code;
                    $name = $row->country_name ?: CountryHelper::getName($code);
                    if ($name === $code || empty($name)) {
                        $name = CountryHelper::getName($code) ?? $code;
                    }

                    $count = (int) $row->count;

                    return [
                        'code' => $code,
                        'name' => (string) $name,
                        'count' => $count,
                        'percentage' => $totalPageviews > 0 ? round(($count / $totalPageviews) * 100, 1) : 0.0,
                    ];
                })
                ->sort(function ($a, $b) {
                    if ($a['count'] === $b['count']) {
                        return strcmp($a['name'], $b['name']);
                    }

                    return $b['count'] <=> $a['count'];
                })
                ->take($limit)
                ->values()
                ->toArray();
        });

        return collect($data ?? []);
    }

    /**
     * Get custom event breakdown from metadata column.
     */
    public function getCustomEvents(Site $site, CarbonInterface $start, CarbonInterface $end, int $limit = 10, array $filters = []): Collection
    {
        $cacheKey = $this->cacheKey($site->id, "custom_events_{$limit}", $start, $end, $filters);

        $data = Cache::remember($cacheKey, $this->ttl, function () use ($site, $start, $end, $limit, $filters) {
            $events = Event::where('site_id', $site->id)
                ->whereBetween('created_at', [$start, $end])
                ->tap(fn ($q) => $this->applyFilters($q, $filters))
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
     * Get currently active visitors in the last N minutes.
     */
    public function getCurrentVisitors(Site $site, int $minutes = 5): int
    {
        return (int) Event::where('site_id', $site->id)
            ->where('created_at', '>=', now()->subMinutes($minutes))
            ->distinct('visitor_hash')
            ->count('visitor_hash');
    }

    /**
     * Get bounce rate (percentage of single-pageview visitor sessions).
     */
    public function getBounceRate(Site $site, CarbonInterface $start, CarbonInterface $end, array $filters = []): float
    {
        $cacheKey = $this->cacheKey($site->id, 'bounce_rate', $start, $end, $filters);

        return (float) $this->rememberCache($site->id, $cacheKey, function () use ($site, $start, $end, $filters) {
            $visitorCounts = Event::where('site_id', $site->id)
                ->whereBetween('created_at', [$start, $end])
                ->tap(fn ($q) => $this->applyFilters($q, $filters))
                ->select('visitor_hash', DB::raw('count(*) as views'))
                ->groupBy('visitor_hash')
                ->get();

            $totalVisitors = $visitorCounts->count();
            if ($totalVisitors === 0) {
                return 0.0;
            }

            $bounces = $visitorCounts->where('views', 1)->count();

            return round(($bounces / $totalVisitors) * 100, 1);
        });
    }

    /**
     * Get average visit duration in seconds across visitor sessions.
     */
    public function getAvgVisitDuration(Site $site, CarbonInterface $start, CarbonInterface $end): int
    {
        $cacheKey = $this->cacheKey($site->id, 'avg_visit_duration', $start, $end);

        return (int) $this->rememberCache($site->id, $cacheKey, function () use ($site, $start, $end) {
            $sessions = Event::where('site_id', $site->id)
                ->whereBetween('created_at', [$start, $end])
                ->select('visitor_hash', DB::raw('MIN(created_at) as first_seen'), DB::raw('MAX(created_at) as last_seen'))
                ->groupBy('visitor_hash')
                ->get();

            $multiEventSessions = $sessions->filter(function ($s) {
                return $s->first_seen !== $s->last_seen;
            });

            if ($multiEventSessions->isEmpty()) {
                return 0;
            }

            $totalDuration = $multiEventSessions->sum(function ($s) {
                return Carbon::parse($s->last_seen)->diffInSeconds(Carbon::parse($s->first_seen));
            });

            return (int) round($totalDuration / $multiEventSessions->count());
        });
    }

    /**
     * Get UTM campaign breakdown for site and date range.
     */
    public function getUtmCampaigns(Site $site, CarbonInterface $start, CarbonInterface $end, int $limit = 10, array $filters = []): Collection
    {
        $cacheKey = $this->cacheKey($site->id, "utm_campaigns_{$limit}", $start, $end, $filters);

        $data = $this->rememberCache($site->id, $cacheKey, function () use ($site, $start, $end, $limit, $filters) {
            $totalPageviews = $this->getPageviews($site, $start, $end, $filters);

            $results = Event::where('site_id', $site->id)
                ->whereBetween('created_at', [$start, $end])
                ->tap(fn ($q) => $this->applyFilters($q, $filters))
                ->whereNotNull('utm_campaign')
                ->where('utm_campaign', '!=', '')
                ->select('utm_campaign', 'utm_source', 'utm_medium', DB::raw('count(*) as count'))
                ->groupBy('utm_campaign', 'utm_source', 'utm_medium')
                ->orderByDesc('count')
                ->limit($limit)
                ->get();

            return $results->map(function ($row) use ($totalPageviews) {
                $count = (int) $row->count;

                return [
                    'campaign' => (string) $row->utm_campaign,
                    'source' => $row->utm_source ? (string) $row->utm_source : null,
                    'medium' => $row->utm_medium ? (string) $row->utm_medium : null,
                    'count' => $count,
                    'percentage' => $totalPageviews > 0 ? round(($count / $totalPageviews) * 100, 1) : 0.0,
                ];
            })->toArray();
        });

        return collect($data ?? []);
    }

    /**
     * Get complete dashboard overview payload.
     */
    public function getOverview(Site $site, CarbonInterface $start, CarbonInterface $end, array $filters = []): array
    {
        return [
            'total_pageviews' => $this->getPageviews($site, $start, $end, $filters),
            'unique_visitors' => $this->getUniqueVisitors($site, $start, $end, $filters),
            'current_visitors' => $this->getCurrentVisitors($site),
            'bounce_rate' => $this->getBounceRate($site, $start, $end, $filters),
            'avg_duration' => $this->getAvgVisitDuration($site, $start, $end, $filters),
            'top_pages' => $this->getTopPages($site, $start, $end, 10, $filters),
            'top_referrers' => $this->getTopReferrers($site, $start, $end, 10, $filters),
            'daily_pageviews' => $this->getDailyPageviews($site, $start, $end, $filters),
            'device_breakdown' => $this->getDeviceBreakdown($site, $start, $end, $filters),
            'top_browsers' => $this->getTopBrowsers($site, $start, $end, 10, $filters),
            'top_os' => $this->getTopOperatingSystems($site, $start, $end, 10, $filters),
            'top_countries' => $this->getTopCountries($site, $start, $end, 10, $filters),
            'utm_campaigns' => $this->getUtmCampaigns($site, $start, $end, 10, $filters),
            'custom_events' => $this->getCustomEvents($site, $start, $end, 10, $filters),
            'goals' => $this->getGoals($site, $start, $end, $filters),
        ];
    }

    /**
     * Generate deterministic cache key.
     */
    protected function cacheKey(int $siteId, string $metric, CarbonInterface $start, CarbonInterface $end, array $filters = [], array $extra = []): string
    {
        $sStr = $start->format('Y-m-d');
        $eStr = $end->format('Y-m-d');

        $key = "lumina:analytics:{$siteId}:{$metric}:{$sStr}:{$eStr}";

        if (! empty($filters)) {
            ksort($filters);
            $key .= ':f_'.md5(json_encode($filters));
        }

        if (! empty($extra)) {
            $key .= ':'.implode(':', $extra);
        }

        return $key;
    }

    /**
     * Get summary KPIs for custom events.
     */
    public function getCustomEventSummary(Site $site, CarbonInterface $start, CarbonInterface $end, ?string $selectedEvent = null): array
    {
        $cacheKey = $this->cacheKey($site->id, 'custom_event_summary', $start, $end, [], [$selectedEvent ?? 'all']);

        return Cache::remember($cacheKey, $this->ttl, function () use ($site, $start, $end, $selectedEvent) {
            $query = Event::where('site_id', $site->id)
                ->whereBetween('created_at', [$start, $end])
                ->whereNotNull('metadata');

            $events = $query->get()->filter(fn ($e) => is_array($e->metadata) && isset($e->metadata['name']));

            if ($selectedEvent) {
                $events = $events->filter(fn ($e) => $e->metadata['name'] === $selectedEvent);
            }

            $totalEvents = $events->count();
            $grouped = $events->groupBy(fn ($e) => $e->metadata['name']);
            $uniqueEventNames = $grouped->keys()->count();

            $topEventName = null;
            if ($uniqueEventNames > 0) {
                $topEventName = $grouped->map->count()->sortDesc()->keys()->first();
            }

            return [
                'total_custom_events' => $totalEvents,
                'unique_event_names' => $uniqueEventNames,
                'top_event_name' => $topEventName,
            ];
        });
    }

    /**
     * Get list of all distinct custom event names with counts.
     */
    public function getCustomEventsList(Site $site, CarbonInterface $start, CarbonInterface $end): Collection
    {
        $cacheKey = $this->cacheKey($site->id, 'custom_events_list', $start, $end);

        $data = Cache::remember($cacheKey, $this->ttl, function () use ($site, $start, $end) {
            $events = Event::where('site_id', $site->id)
                ->whereBetween('created_at', [$start, $end])
                ->whereNotNull('metadata')
                ->get()
                ->filter(fn ($e) => is_array($e->metadata) && isset($e->metadata['name']));

            $totalEvents = $events->count();

            return $events
                ->groupBy(fn ($e) => $e->metadata['name'])
                ->map(function ($group, $name) use ($totalEvents) {
                    $count = $group->count();

                    return [
                        'name' => (string) $name,
                        'count' => $count,
                        'percentage' => $totalEvents > 0 ? round(($count / $totalEvents) * 100, 1) : 0.0,
                        'last_seen' => $group->sortByDesc('created_at')->first()->created_at->toDateTimeString(),
                    ];
                })
                ->sortByDesc('count')
                ->values()
                ->toArray();
        });

        return collect($data ?? []);
    }

    /**
     * Get daily timeseries for custom events.
     */
    public function getCustomEventTimeline(Site $site, CarbonInterface $start, CarbonInterface $end, ?string $eventName = null): Collection
    {
        $cacheKey = $this->cacheKey($site->id, 'custom_event_timeline', $start, $end, [], [$eventName ?? 'all']);

        $data = Cache::remember($cacheKey, $this->ttl, function () use ($site, $start, $end, $eventName) {
            $events = Event::where('site_id', $site->id)
                ->whereBetween('created_at', [$start, $end])
                ->whereNotNull('metadata')
                ->get()
                ->filter(fn ($e) => is_array($e->metadata) && isset($e->metadata['name']));

            if ($eventName) {
                $events = $events->filter(fn ($e) => $e->metadata['name'] === $eventName);
            }

            $grouped = $events->groupBy(fn ($e) => $e->created_at->format('Y-m-d'));

            $series = [];
            $curr = $start->copy()->startOfDay();
            $last = $end->copy()->startOfDay();

            while ($curr->lte($last)) {
                $dateStr = $curr->format('Y-m-d');
                $dayEvents = $grouped->get($dateStr, collect());
                $series[] = [
                    'date' => $dateStr,
                    'count' => $dayEvents->count(),
                ];
                $curr = $curr->addDay();
            }

            return $series;
        });

        return collect($data ?? []);
    }

    /**
     * Get property keys for a custom event.
     */
    public function getCustomEventPropertyKeys(Site $site, string $eventName, CarbonInterface $start, CarbonInterface $end): array
    {
        $cacheKey = $this->cacheKey($site->id, 'custom_event_property_keys', $start, $end, [], [$eventName]);

        return Cache::remember($cacheKey, $this->ttl, function () use ($site, $start, $end, $eventName) {
            $events = Event::where('site_id', $site->id)
                ->whereBetween('created_at', [$start, $end])
                ->whereNotNull('metadata')
                ->get()
                ->filter(fn ($e) => is_array($e->metadata) && isset($e->metadata['name']) && $e->metadata['name'] === $eventName);

            $keys = collect();

            foreach ($events as $event) {
                if (isset($event->metadata['props']) && is_array($event->metadata['props'])) {
                    $keys = $keys->merge(array_keys($event->metadata['props']));
                }
            }

            return $keys->unique()->sort()->values()->toArray();
        });
    }

    /**
     * Get property value breakdown.
     */
    public function getCustomEventPropertyBreakdown(Site $site, string $eventName, string $propertyKey, CarbonInterface $start, CarbonInterface $end, int $limit = 10): Collection
    {
        $cacheKey = $this->cacheKey($site->id, 'custom_event_property_breakdown', $start, $end, [], [$eventName, $propertyKey, $limit]);

        $data = Cache::remember($cacheKey, $this->ttl, function () use ($site, $start, $end, $eventName, $propertyKey, $limit) {
            $events = Event::where('site_id', $site->id)
                ->whereBetween('created_at', [$start, $end])
                ->whereNotNull('metadata')
                ->get()
                ->filter(fn ($e) => is_array($e->metadata) && isset($e->metadata['name']) && $e->metadata['name'] === $eventName)
                ->filter(fn ($e) => isset($e->metadata['props']) && is_array($e->metadata['props']) && array_key_exists($propertyKey, $e->metadata['props']));

            $total = $events->count();

            return $events
                ->groupBy(function ($e) use ($propertyKey) {
                    $value = $e->metadata['props'][$propertyKey];
                    if (is_scalar($value) || is_null($value)) {
                        return (string) $value;
                    }

                    return json_encode($value);
                })
                ->map(function ($group, $value) use ($total) {
                    $count = $group->count();

                    return [
                        'value' => $value === '' ? '(empty)' : $value,
                        'count' => $count,
                        'percentage' => $total > 0 ? round(($count / $total) * 100, 1) : 0.0,
                    ];
                })
                ->sortByDesc('count')
                ->take($limit)
                ->values()
                ->toArray();
        });

        return collect($data ?? []);
    }

    /**
     * Get custom event logs.
     */
    public function getCustomEventLogs(Site $site, CarbonInterface $start, CarbonInterface $end, ?string $eventName = null, int $limit = 50): Collection
    {
        $cacheKey = $this->cacheKey($site->id, 'custom_event_logs', $start, $end, [], [$eventName ?? 'all', $limit]);

        $data = Cache::remember($cacheKey, $this->ttl, function () use ($site, $start, $end, $eventName, $limit) {
            $query = Event::where('site_id', $site->id)
                ->whereBetween('created_at', [$start, $end])
                ->whereNotNull('metadata')
                ->latest();

            $events = $query->get()->filter(fn ($e) => is_array($e->metadata) && isset($e->metadata['name']));

            if ($eventName) {
                $events = $events->filter(fn ($e) => $e->metadata['name'] === $eventName);
            }

            return $events->take($limit)->map(function ($e) {
                $props = $e->metadata['props'] ?? null;

                return [
                    'id' => $e->id,
                    'created_at' => $e->created_at->toDateTimeString(),
                    'path' => $e->path,
                    'visitor_hash' => $e->visitor_hash,
                    'device_type' => is_object($e->device_type) ? $e->device_type->value : (string) $e->device_type,
                    'browser' => $e->browser,
                    'os' => $e->os,
                    'country_name' => $e->country_name ?? $e->country,
                    'country_code' => $e->country_code,
                    'event_name' => $e->metadata['name'],
                    'props' => $props,
                ];
            })->values()->toArray();
        });

        return collect($data ?? []);
    }

    /**
     * Get goals and conversion rates.
     */
    public function getGoals(Site $site, CarbonInterface $start, CarbonInterface $end, array $filters = []): Collection
    {
        $cacheKey = $this->cacheKey($site->id, 'goals', $start, $end, $filters);

        $data = Cache::remember($cacheKey, $this->ttl, function () use ($site, $start, $end, $filters) {
            $goals = $site->goals;

            if ($goals->isEmpty()) {
                return [];
            }

            $uniqueVisitors = $this->getUniqueVisitors($site, $start, $end);
            $results = [];

            foreach ($goals as $goal) {
                $query = Event::where('site_id', $site->id)
                    ->whereBetween('created_at', [$start, $end])
                ->tap(fn ($q) => $this->applyFilters($q, $filters));

                if ($goal->target_type === 'path') {
                    if (str_contains($goal->target_value, '*')) {
                        $pattern = str_replace('*', '%', $goal->target_value);
                        $query->where('path', 'like', $pattern);
                    } else {
                        $query->where('path', $goal->target_value);
                    }
                    $events = $query->get();
                } elseif ($goal->target_type === 'custom_event') {

                    $events = $query->whereNotNull('metadata')->get()
                        ->filter(fn ($e) => is_array($e->metadata) && isset($e->metadata['name']) && $e->metadata['name'] === $goal->target_value);
                } else {
                    $events = collect();
                }

                $completions = $events->count();
                $conversionRate = $uniqueVisitors > 0 ? round(($completions / $uniqueVisitors) * 100, 1) : 0.0;

                $grouped = $events->groupBy(fn ($e) => $e->created_at->format('Y-m-d'));

                $trend = [];
                $curr = $start->copy()->startOfDay();
                $last = $end->copy()->startOfDay();

                while ($curr->lte($last)) {
                    $dateStr = $curr->format('Y-m-d');
                    $dayEvents = $grouped->get($dateStr, collect());
                    $trend[] = [
                        'date' => $dateStr,
                        'completions' => $dayEvents->count(),
                    ];
                    $curr = $curr->addDay();
                }

                $results[] = [
                    'id' => $goal->id,
                    'name' => $goal->name,
                    'target_type' => $goal->target_type,
                    'target_value' => $goal->target_value,
                    'completions' => $completions,
                    'conversion_rate' => $conversionRate,
                    'trend' => $trend,
                ];
            }

            return $results;
        });

        return collect($data ?? []);
    }

    /**
     * Cache helper with tag support if available.
     */
    protected function rememberCache(int $siteId, string $key, \Closure $callback): mixed
    {
        if (Cache::supportsTags()) {
            return Cache::tags(["lumina:site:{$siteId}"])->remember($key, $this->ttl, $callback);
        }

        return Cache::remember($key, $this->ttl, $callback);
    }
}
