<?php

namespace Lumina\Core\Livewire;

use Carbon\Carbon;
use Livewire\Component;
use Lumina\Core\Models\Site;
use Lumina\Core\Services\AnalyticsService;

class Dashboard extends Component
{
    public Site $site;

    public string $period = '30d';

    public ?string $startDate = null;

    public ?string $endDate = null;

    public function mount(Site $site, string $period = '30d'): void
    {
        $this->site = $site;
        $this->period = $period;
    }

    public function setPeriod(string $period): void
    {
        $this->period = $period;
    }

    public function render(AnalyticsService $analytics)
    {
        [$start, $end] = $this->resolveDateRange();

        $overview = $analytics->getOverview($this->site, $start, $end);

        return view('lumina::livewire.dashboard', array_merge($overview, [
            'period' => $this->period,
            'start' => $start,
            'end' => $end,
        ]));
    }

    protected function resolveDateRange(): array
    {
        if ($this->period === '7d') {
            return [
                now()->subDays(6)->startOfDay(),
                now()->endOfDay(),
            ];
        }

        if ($this->period === 'custom' && $this->startDate && $this->endDate) {
            return [
                Carbon::parse($this->startDate)->startOfDay(),
                Carbon::parse($this->endDate)->endOfDay(),
            ];
        }

        return [
            now()->subDays(29)->startOfDay(),
            now()->endOfDay(),
        ];
    }
}
