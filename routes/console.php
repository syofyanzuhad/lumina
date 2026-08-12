<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Framework pruning (MassPrunable models, e.g. the Event model's global
// retention default).
Schedule::command('model:prune')->daily();

// Per-site retention-aware pruning of raw events. Anonymous daily_visitor_stats
// aggregates are deliberately kept, so historical visitor counts survive.
Schedule::command('lumina:prune-events')->dailyAt('02:00');

// Reconcile any daily_visitor_stats rows lost by an interrupted upsert.
Schedule::command('lumina:backfill-visitor-stats')->dailyAt('03:00');
