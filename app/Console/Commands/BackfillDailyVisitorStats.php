<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Lumina\Core\Models\Site;

class BackfillDailyVisitorStats extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'lumina:backfill-visitor-stats {--site= : Backfill for a specific site ID} {--days= : Limit backfill to the last N days}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Backfill daily_visitor_stats table from existing events table in safe site/date chunks';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Starting backfill for daily_visitor_stats...');

        $siteIdOption = $this->option('site');
        $daysOption = $this->option('days');
        $isSqlite = DB::getDriverName() === 'sqlite';

        $sitesQuery = Site::query()->orderBy('id');
        if ($siteIdOption) {
            $sitesQuery->where('id', (int) $siteIdOption);
        }

        $sitesQuery->each(function (Site $site) use ($daysOption, $isSqlite): void {
            $dateRangeQuery = DB::table('events')
                ->where('site_id', $site->id);

            if ($daysOption !== null && (int) $daysOption > 0) {
                $dateRangeQuery->where('created_at', '>=', now()->subDays((int) $daysOption)->startOfDay());
            }

            $minMax = $dateRangeQuery
                ->selectRaw('MIN(created_at) as min_date, MAX(created_at) as max_date')
                ->first();

            if (! $minMax || ! $minMax->min_date || ! $minMax->max_date) {
                return;
            }

            $startDate = now()->parse($minMax->min_date)->startOfDay();
            $endDate = now()->parse($minMax->max_date)->endOfDay();

            // Process day by day or in weekly chunks to prevent holding table locks
            $current = $startDate->copy();
            while ($current->lte($endDate)) {
                $chunkStart = $current->copy()->startOfDay();
                $chunkEnd = $current->copy()->addDays(7)->endOfDay();
                if ($chunkEnd->gt($endDate)) {
                    $chunkEnd = $endDate->copy();
                }

                $sql = $isSqlite
                    ? 'INSERT OR IGNORE INTO daily_visitor_stats (site_id, date, visitor_hash, views, created_at, updated_at)
                       SELECT site_id, strftime(\'%Y-%m-%d\', created_at) as date, COALESCE(visitor_id, visitor_hash) as visitor_hash, COUNT(*) as views, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP
                       FROM events
                       WHERE site_id = ? AND created_at BETWEEN ? AND ?
                       GROUP BY site_id, strftime(\'%Y-%m-%d\', created_at), COALESCE(visitor_id, visitor_hash)'
                    : 'INSERT IGNORE INTO daily_visitor_stats (site_id, date, visitor_hash, views, created_at, updated_at)
                       SELECT site_id, DATE(created_at) as date, COALESCE(visitor_id, visitor_hash) as visitor_hash, COUNT(*) as views, NOW(), NOW()
                       FROM events
                       WHERE site_id = ? AND created_at BETWEEN ? AND ?
                       GROUP BY site_id, DATE(created_at), COALESCE(visitor_id, visitor_hash)';

                DB::statement($sql, [
                    $site->id,
                    $chunkStart->toDateTimeString(),
                    $chunkEnd->toDateTimeString(),
                ]);

                $current = $chunkEnd->copy()->addSecond()->startOfDay();
            }
        });

        $this->info('Backfill completed successfully!');

        return self::SUCCESS;
    }
}
