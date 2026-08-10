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
    public function handle()
    {
        $this->info('Starting backfill for daily_visitor_stats...');

        DB::statement('
            INSERT INTO daily_visitor_stats (site_id, date, visitor_hash, views, created_at, updated_at)
            SELECT site_id, DATE(created_at) as date, visitor_hash, COUNT(*) as views, NOW(), NOW()
            FROM events
            GROUP BY site_id, DATE(created_at), visitor_hash
            ON DUPLICATE KEY UPDATE views = VALUES(views), updated_at = NOW()
        ');

        $this->info('Backfill completed successfully!');
    }
}
