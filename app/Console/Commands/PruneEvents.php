<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Lumina\Core\Models\Event;
use Lumina\Core\Models\Site;

class PruneEvents extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'lumina:prune-events';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete raw events older than each site\'s retention period, keeping anonymous daily_visitor_stats aggregates';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $defaultDays = (int) config('lumina.retention_days', 90);
        $deleted = 0;

        Site::query()->orderBy('id')->each(function (Site $site) use (&$deleted, $defaultDays): void {
            $days = $site->retention_days ?? $defaultDays;
            $cutoff = now()->subDays($days);

            $deleted += Event::query()
                ->where('site_id', $site->id)
                ->where('created_at', '<', $cutoff)
                ->delete();
        });

        $this->info("Pruned {$deleted} events older than their site retention period.");

        return self::SUCCESS;
    }
}
