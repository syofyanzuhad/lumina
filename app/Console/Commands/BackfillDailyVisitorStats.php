<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class BackfillDailyVisitorStats extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'lumina:backfill-visitor-stats';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Backfill daily_visitor_stats table from existing events table';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Starting backfill for daily_visitor_stats...');

        // INSERT ... IGNORE (portable to SQLite via OR IGNORE): missing
        // site/date/visitor rows are filled with counts recomputed from the
        // events table, while existing aggregate rows are left untouched — so
        // pruning raw events can never deflate historical aggregate counts.
        $sql = DB::getDriverName() === 'sqlite'
            ? 'INSERT OR IGNORE INTO daily_visitor_stats (site_id, date, visitor_hash, views, created_at, updated_at)
               SELECT site_id, DATE(created_at) as date, visitor_hash, COUNT(*) as views, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP
               FROM events
               GROUP BY site_id, DATE(created_at), visitor_hash'
            : 'INSERT IGNORE INTO daily_visitor_stats (site_id, date, visitor_hash, views, created_at, updated_at)
               SELECT site_id, DATE(created_at) as date, visitor_hash, COUNT(*) as views, NOW(), NOW()
               FROM events
               GROUP BY site_id, DATE(created_at), visitor_hash';

        DB::statement($sql);

        $this->info('Backfill completed successfully!');

        return self::SUCCESS;
    }
}
