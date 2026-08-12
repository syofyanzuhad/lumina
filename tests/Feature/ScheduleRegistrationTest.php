<?php

use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;

test('the scheduler registers the per-site retention prune command', function () {
    $events = collect(app(Schedule::class)->events());

    expect($events->contains(fn (Event $event) => str_contains($event->command, 'lumina:prune-events')))->toBeTrue();
});

test('the scheduler registers the daily visitor stats backfill command', function () {
    $events = collect(app(Schedule::class)->events());

    expect($events->contains(fn (Event $event) => str_contains($event->command, 'lumina:backfill-visitor-stats')))->toBeTrue();
});

test('the retention commands run on the documented schedule', function () {
    $prune = collect(app(Schedule::class)->events())
        ->first(fn (Event $event) => str_contains($event->command, 'lumina:prune-events'));

    $backfill = collect(app(Schedule::class)->events())
        ->first(fn (Event $event) => str_contains($event->command, 'lumina:backfill-visitor-stats'));

    expect($prune)->not->toBeNull();
    expect($backfill)->not->toBeNull();

    // lumina:prune-events -> dailyAt('02:00'); backfill -> dailyAt('03:00').
    expect($prune->expression)->toBe('0 2 * * *');
    expect($backfill->expression)->toBe('0 3 * * *');
});
