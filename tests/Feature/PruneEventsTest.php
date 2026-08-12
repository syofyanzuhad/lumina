<?php

namespace Tests\Feature;

use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Lumina\Core\Models\Event;
use Lumina\Core\Models\Site;
use Tests\TestCase;

class PruneEventsTest extends TestCase
{
    use RefreshDatabase;

    private function createSite(array $attributes = []): Site
    {
        return Site::factory()->create($attributes);
    }

    private function createEvent(Site $site, string $path, CarbonImmutable $createdAt): Event
    {
        return Event::create([
            'site_id' => $site->id,
            'path' => $path,
            'visitor_hash' => str_repeat((string) mt_rand(0, 9), 64),
            'created_at' => $createdAt,
        ]);
    }

    public function test_deletes_events_older_than_default_retention(): void
    {
        config(['lumina.retention_days' => 90]);
        $site = $this->createSite();

        $old = $this->createEvent($site, '/old', now()->subDays(120));
        $recent = $this->createEvent($site, '/recent', now()->subDays(10));

        Artisan::call('lumina:prune-events');

        $this->assertDatabaseMissing('events', ['id' => $old->id]);
        $this->assertDatabaseHas('events', ['id' => $recent->id]);
    }

    public function test_respects_per_site_retention_override(): void
    {
        config(['lumina.retention_days' => 90]);

        $shortSite = $this->createSite(['retention_days' => 7]);
        $longSite = $this->createSite(['retention_days' => 365]);

        $shortOld = $this->createEvent($shortSite, '/short-old', now()->subDays(30));
        $shortNew = $this->createEvent($shortSite, '/short-new', now()->subDays(2));
        $longOld = $this->createEvent($longSite, '/long-old', now()->subDays(30));

        Artisan::call('lumina:prune-events');

        $this->assertDatabaseMissing('events', ['id' => $shortOld->id]);
        $this->assertDatabaseHas('events', ['id' => $shortNew->id]);
        $this->assertDatabaseHas('events', ['id' => $longOld->id]);
    }

    public function test_zero_retention_means_keep_forever(): void
    {
        config(['lumina.retention_days' => 7]);
        $site = $this->createSite(['retention_days' => 0]);

        $event = $this->createEvent($site, '/ancient', now()->subYears(2));

        Artisan::call('lumina:prune-events');

        $this->assertDatabaseHas('events', ['id' => $event->id]);
    }

    public function test_keeps_daily_visitor_stats_aggregates(): void
    {
        $site = $this->createSite();
        $event = $this->createEvent($site, '/old', now()->subDays(120));

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
    }

    public function test_chunks_deletes_across_many_rows(): void
    {
        $site = $this->createSite();

        for ($i = 0; $i < 25; $i++) {
            $this->createEvent($site, "/page-{$i}", now()->subDays(200));
        }

        Artisan::call('lumina:prune-events');

        $this->assertSame(0, Event::where('site_id', $site->id)->count());
    }
}
