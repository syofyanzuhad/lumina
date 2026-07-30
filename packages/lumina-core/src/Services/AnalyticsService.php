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
        Cache::forget("lumina:analytics:{$site->id}:custom_events_list");
        Cache::forget("lumina:analytics:{$site->id}:custom_event_summary");
        Cache::forget("lumina:analytics:{$site->id}:custom_event_timeline");
        Cache::forget("lumina:analytics:{$site->id}:custom_event_property_keys");
        Cache::forget("lumina:analytics:{$site->id}:custom_event_property_breakdown");
        Cache::forget("lumina:analytics:{$site->id}:custom_event_logs");
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
    protected function cacheKey(int $siteId, string $metric, CarbonInterface $start, CarbonInterface $end, array $extra = []): string
    {
        $sStr = $start->format('Y-m-d');
        $eStr = $end->format('Y-m-d');

        $key = "lumina:analytics:{$siteId}:{$metric}:{$sStr}:{$eStr}";

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
        $cacheKey = $this->cacheKey($site->id, 'custom_event_summary', $start, $end, [$selectedEvent ?? 'all']);

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
        $cacheKey = $this->cacheKey($site->id, 'custom_event_timeline', $start, $end, [$eventName ?? 'all']);

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
        $cacheKey = $this->cacheKey($site->id, 'custom_event_property_keys', $start, $end, [$eventName]);

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
        $cacheKey = $this->cacheKey($site->id, 'custom_event_property_breakdown', $start, $end, [$eventName, $propertyKey, $limit]);

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
        $cacheKey = $this->cacheKey($site->id, 'custom_event_logs', $start, $end, [$eventName ?? 'all', $limit]);

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
}
