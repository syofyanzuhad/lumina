<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
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
     * How many rows a single DELETE statement removes at most. Bounded so a
     * large first run can never hold a table lock for the whole sweep.
     */
    private const BATCH_SIZE = 1000;

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $defaultDays = (int) config('lumina.retention_days', 90);
        $deleted = 0;

        Site::query()->orderBy('id')->each(function (Site $site) use (&$deleted, $defaultDays): void {
            $days = $site->retention_days ?? $defaultDays;

            // 0 (or negative) means "keep forever" — never prune this site.
            if ($days <= 0) {
                return;
            }

            $cutoff = now()->subDays($days);

            // Delete in bounded batches keyed by primary key, so MySQL never
            // holds a table lock and SQLite stays within one transaction per
            // statement. The subquery re-selects each iteration because
            // DELETE ... LIMIT is not portable across drivers.
            do {
                $batchIds = Event::query()
                    ->where('site_id', $site->id)
                    ->where('created_at', '<', $cutoff)
                    ->orderBy('id')
                    ->limit(self::BATCH_SIZE)
                    ->pluck('id')
                    ->all();

                if ($batchIds === []) {
                    break;
                }

                $deleted += (int) DB::table('events')
                    ->whereIn('id', $batchIds)
                    ->delete();
            } while (count($batchIds) === self::BATCH_SIZE);
        });

        $this->info("Pruned {$deleted} events older than their site retention period.");

        return self::SUCCESS;
    }
}
