<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class BackfillCleanPath extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'lumina:backfill-clean-path';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Backfill clean_path column on legacy events rows (where clean_path is NULL) from the raw path column';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $this->info('Starting backfill for clean_path...');

        $sql = DB::getDriverName() === 'sqlite'
            ? "UPDATE events SET clean_path = CASE WHEN instr(path, '?') > 0 THEN substr(path, 1, instr(path, '?') - 1) ELSE path END WHERE clean_path IS NULL"
            : "UPDATE events SET clean_path = SUBSTRING_INDEX(path, '?', 1) WHERE clean_path IS NULL";

        DB::statement($sql);

        $this->info('Backfill completed successfully!');
    }
}
