<?php

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Lumina\Core\Models\Event;
use Lumina\Core\Models\Site;

test('deletes events older than default retention', function () {
    config(['lumina.retention_days' => 90]);
    $site = createSite();

    $old = createEvent($site, '/old', now()->subDays(120));
    $recent = createEvent($site, '/recent', now()->subDays(10));

    Artisan::call('lumina:prune-events');

    $this->assertDatabaseMissing('events', ['id' => $old->id]);
    $this->assertDatabaseHas('events', ['id' => $recent->id]);
});

test('respects per site retention override', function () {
    config(['lumina.retention_days' => 90]);

    $shortSite = createSite(['retention_days' => 7]);
    $longSite = createSite(['retention_days' => 365]);

    $shortOld = createEvent($shortSite, '/short-old', now()->subDays(30));
    $shortNew = createEvent($shortSite, '/short-new', now()->subDays(2));
    $longOld = createEvent($longSite, '/long-old', now()->subDays(30));

    Artisan::call('lumina:prune-events');

    $this->assertDatabaseMissing('events', ['id' => $shortOld->id]);
    $this->assertDatabaseHas('events', ['id' => $shortNew->id]);
    $this->assertDatabaseHas('events', ['id' => $longOld->id]);
});

test('zero retention means keep forever', function () {
    config(['lumina.retention_days' => 7]);
    $site = createSite(['retention_days' => 0]);

    $event = createEvent($site, '/ancient', now()->subYears(2));

    Artisan::call('lumina:prune-events');

    $this->assertDatabaseHas('events', ['id' => $event->id]);
});

test('keeps daily visitor stats aggregates', function () {
    $site = createSite();
    $event = createEvent($site, '/old', now()->subDays(120));

    DB::table('daily_visitor_stats')->insert([
        'site_id' => $site->id,
        'date' => $event->created_at->toDateString(),
        'visitor_hash' => $event->visitor_hash,
        'views' => 1,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    Artisan::call('lumina:prune-events');

    $this->assertDatabaseMissing('events', ['id' => $event->id]);
    $this->assertSame(1, DB::table('daily_visitor_stats')->where('site_id', $site->id)->count());
});

test('chunks deletes across many rows', function () {
    $site = createSite();

    for ($i = 0; $i < 25; $i++) {
        createEvent($site, "/page-{$i}", now()->subDays(200));
    }

    Artisan::call('lumina:prune-events');

    $this->assertSame(0, Event::where('site_id', $site->id)->count());
});

function createSite(array $attributes = []): Site
{
    return Site::factory()->create($attributes);
}

function createEvent(Site $site, string $path, CarbonImmutable $createdAt): Event
{
    return Event::create([
        'site_id' => $site->id,
        'path' => $path,
        'visitor_hash' => str_repeat((string) mt_rand(0, 9), 64),
        'created_at' => $createdAt,
    ]);
}
