<?php

namespace Lumina\Core\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Lumina\Core\Models\Site;
use Lumina\Core\Services\AnalyticsService;

class LuminaOverviewWidget extends BaseWidget
{
    public ?Site $site = null;

    public string $period = '30d';

    protected function getStats(): array
    {
        if (! $this->site) {
            $this->site = Site::first();
        }

        if (! $this->site) {
            return [
                Stat::make('Analytics', 'No site registered')
                    ->description('Add a site to start collecting analytics'),
            ];
        }

        $analytics = app(AnalyticsService::class);
        $start = now()->subDays($this->period === '7d' ? 6 : 29)->startOfDay();
        $end = now()->endOfDay();

        return [
            Stat::make('Currently Online', $analytics->getCurrentVisitors($this->site))
                ->description('Active in last 5 minutes')
                ->color('success'),

            Stat::make('Total Pageviews', number_format($analytics->getPageviews($this->site, $start, $end)))
                ->description('Total raw page visits')
                ->color('primary'),

            Stat::make('Unique Visitors', number_format($analytics->getUniqueVisitors($this->site, $start, $end)))
                ->description('Distinct daily hashed visitors')
                ->color('info'),

            Stat::make('Bounce / Duration', $analytics->getBounceRate($this->site, $start, $end).'%')
                ->description('Avg duration: '.$analytics->getAvgVisitDuration($this->site, $start, $end).'s')
                ->color('warning'),
        ];
    }
}
