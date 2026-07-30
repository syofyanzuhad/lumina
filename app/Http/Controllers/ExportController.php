<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Lumina\Core\Models\Event;
use Lumina\Core\Models\Site;
use Lumina\Core\Services\AnalyticsService;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportController extends Controller
{
    public function export(Request $request, Site $site, AnalyticsService $analytics): StreamedResponse
    {
        Gate::authorize('view', $site);

        $type = $request->query('type', 'pageviews');
        $format = $request->query('format', 'csv');
        $period = $request->query('period', '30d');
        [$start, $end] = $this->resolveDateRange($period, $request->query('start_date'), $request->query('end_date'));

        if ($type === 'summary') {
            return $this->exportSummary($site, $start, $end, $format, $analytics);
        }

        if ($format === 'json') {
            return $this->exportJsonStream($site, $start, $end, $type);
        }

        return $this->exportCsvStream($site, $start, $end, $type);
    }

    protected function exportCsvStream(Site $site, CarbonInterface $start, CarbonInterface $end, string $type): StreamedResponse
    {
        $filename = "{$site->domain}-{$type}-export.csv";

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        $callback = function () use ($site, $start, $end, $type) {
            $file = fopen('php://output', 'w');

            if ($type === 'events') {
                fputcsv($file, ['ID', 'Event Name', 'Path', 'Metadata', 'Referrer', 'Device Type', 'Created At']);

                $query = Event::where('site_id', $site->id)
                    ->whereBetween('created_at', [$start, $end])
                    ->whereNotNull('metadata');

                $query->chunk(1000, function ($events) use ($file) {
                    foreach ($events as $event) {
                        $metaName = is_array($event->metadata) ? ($event->metadata['name'] ?? '') : '';
                        $metaJson = is_array($event->metadata) ? json_encode($event->metadata) : '';
                        fputcsv($file, [
                            $event->id,
                            $metaName,
                            $event->path,
                            $metaJson,
                            $event->referrer,
                            $event->device_type->value ?? 'unknown',
                            $event->created_at->toDateTimeString(),
                        ]);
                    }
                });
            } else {
                fputcsv($file, ['ID', 'Path', 'Referrer', 'Device Type', 'Browser', 'OS', 'Country', 'Created At']);

                $query = Event::where('site_id', $site->id)
                    ->whereBetween('created_at', [$start, $end])
                    ->whereNull('metadata');

                $query->chunk(1000, function ($events) use ($file) {
                    foreach ($events as $event) {
                        fputcsv($file, [
                            $event->id,
                            $event->path,
                            $event->referrer,
                            $event->device_type->value ?? 'unknown',
                            $event->browser ?? '',
                            $event->os ?? '',
                            $event->country ?? '',
                            $event->created_at->toDateTimeString(),
                        ]);
                    }
                });
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    protected function exportJsonStream(Site $site, CarbonInterface $start, CarbonInterface $end, string $type): StreamedResponse
    {
        $filename = "{$site->domain}-{$type}-export.json";

        $headers = [
            'Content-Type' => 'application/json',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        $callback = function () use ($site, $start, $end, $type) {
            $out = fopen('php://output', 'w');
            fwrite($out, "[\n");

            $query = Event::where('site_id', $site->id)
                ->whereBetween('created_at', [$start, $end]);

            if ($type === 'events') {
                $query->whereNotNull('metadata');
            } else {
                $query->whereNull('metadata');
            }

            $first = true;
            $query->chunk(1000, function ($events) use ($out, &$first) {
                foreach ($events as $event) {
                    if (! $first) {
                        fwrite($out, ",\n");
                    }
                    $first = false;
                    fwrite($out, json_encode($event->toArray()));
                }
            });

            fwrite($out, "\n]");
            fclose($out);
        };

        return response()->stream($callback, 200, $headers);
    }

    protected function exportSummary(Site $site, CarbonInterface $start, CarbonInterface $end, string $format, AnalyticsService $analytics): StreamedResponse
    {
        $overview = $analytics->getOverview($site, $start, $end);

        if ($format === 'json') {
            $filename = "{$site->domain}-summary-export.json";
            $headers = [
                'Content-Type' => 'application/json',
                'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            ];

            return response()->stream(function () use ($overview) {
                echo json_encode($overview, JSON_PRETTY_PRINT);
            }, 200, $headers);
        }

        $filename = "{$site->domain}-summary-export.csv";
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        return response()->stream(function () use ($overview) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['Metric', 'Value']);
            fputcsv($file, ['Total Pageviews', $overview['total_pageviews'] ?? 0]);
            fputcsv($file, ['Unique Visitors', $overview['unique_visitors'] ?? 0]);
            fputcsv($file, []);

            fputcsv($file, ['Top Pages - Path', 'Pageviews', 'Percentage']);
            foreach ($overview['top_pages'] ?? [] as $page) {
                fputcsv($file, [$page['path'], $page['count'], $page['percentage'].'%']);
            }
            fputcsv($file, []);

            fputcsv($file, ['Top Referrers - Referrer', 'Views', 'Percentage']);
            foreach ($overview['top_referrers'] ?? [] as $ref) {
                fputcsv($file, [$ref['referrer'], $ref['count'], $ref['percentage'].'%']);
            }

            fclose($file);
        }, 200, $headers);
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
