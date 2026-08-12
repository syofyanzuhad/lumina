<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Lumina\Core\Enums\DeviceType;
use Lumina\Core\Jobs\InsertEvent;
use Lumina\Core\Models\Event;
use Lumina\Core\Models\Site;
use Tests\TestCase;

class InsertEventJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_repeated_dispatch_with_same_event_id_inserts_only_once(): void
    {
        $site = Site::factory()->create();
        $eventId = (string) Str::uuid();

        // Queue retries re-run the same job with the same event_id.
        InsertEvent::dispatchSync(
            siteId: $site->id,
            path: '/pricing',
            referrer: null,
            visitorHash: str_repeat('a', 64),
            deviceType: DeviceType::Desktop,
            eventId: $eventId,
        );
        InsertEvent::dispatchSync(
            siteId: $site->id,
            path: '/pricing',
            referrer: null,
            visitorHash: str_repeat('a', 64),
            deviceType: DeviceType::Desktop,
            eventId: $eventId,
        );

        $this->assertDatabaseCount('events', 1);

        // Daily stats must also be incremented only once.
        $this->assertSame(1, DB::table('daily_visitor_stats')->where('site_id', $site->id)->value('views'));
    }

    public function test_truncates_overlong_referrer_to_column_limit(): void
    {
        $site = Site::factory()->create();

        InsertEvent::dispatchSync(
            siteId: $site->id,
            path: '/pricing',
            referrer: 'https://example.com/?q='.str_repeat('x', 400),
            visitorHash: str_repeat('b', 64),
            deviceType: DeviceType::Desktop,
        );

        $event = Event::first();

        $this->assertNotNull($event);
        $this->assertLessThanOrEqual(255, strlen((string) $event->referrer));
    }

    public function test_daily_visitor_stats_upsert_increments_views_across_events(): void
    {
        $site = Site::factory()->create();
        $visitorHash = str_repeat('c', 64);

        InsertEvent::dispatchSync(
            siteId: $site->id,
            path: '/home',
            referrer: null,
            visitorHash: $visitorHash,
            deviceType: DeviceType::Desktop,
        );
        InsertEvent::dispatchSync(
            siteId: $site->id,
            path: '/pricing',
            referrer: null,
            visitorHash: $visitorHash,
            deviceType: DeviceType::Desktop,
        );

        $this->assertDatabaseCount('events', 2);
        $this->assertSame(2, DB::table('daily_visitor_stats')->where('site_id', $site->id)->value('views'));
    }

    public function test_daily_visitor_stats_keys_by_resolved_identity_in_mixed_population(): void
    {
        $site = Site::factory()->create();

        // JS visitor: opaque client ID takes precedence over the fallback hash.
        InsertEvent::dispatchSync(
            siteId: $site->id,
            path: '/home',
            referrer: null,
            visitorHash: 'fallback-hash-for-js-visitor',
            visitorId: 'js-visitor-1',
            deviceType: DeviceType::Desktop,
        );
        InsertEvent::dispatchSync(
            siteId: $site->id,
            path: '/pricing',
            referrer: null,
            visitorHash: 'fallback-hash-for-js-visitor',
            visitorId: 'js-visitor-1',
            deviceType: DeviceType::Desktop,
        );

        // Non-JS visitor: falls back to the server-side hash as its identity.
        InsertEvent::dispatchSync(
            siteId: $site->id,
            path: '/blog',
            referrer: null,
            visitorHash: 'non-js-hash-1',
            deviceType: DeviceType::Mobile,
        );

        // The aggregate must be keyed by the resolved identity
        // (COALESCE(visitor_id, visitor_hash)) so a mixed JS/non-JS population
        // never double-counts: two rows (js-visitor-1 with 2 views, and
        // non-js-hash-1 with 1 view), not three.
        $stats = DB::table('daily_visitor_stats')
            ->where('site_id', $site->id)
            ->orderBy('visitor_hash')
            ->get(['visitor_hash', 'views']);

        $this->assertCount(2, $stats);
        $this->assertSame(2, (int) $stats->firstWhere('visitor_hash', 'js-visitor-1')->views);
        $this->assertSame(1, (int) $stats->firstWhere('visitor_hash', 'non-js-hash-1')->views);
    }

    public function test_stores_visitor_and_session_identity(): void
    {
        $site = Site::factory()->create();

        InsertEvent::dispatchSync(
            siteId: $site->id,
            path: '/home',
            referrer: null,
            visitorHash: 'opaque-visitor-1',
            visitorId: 'opaque-visitor-1',
            sessionId: 'session-xyz',
            deviceType: DeviceType::Desktop,
        );

        $event = Event::first();

        $this->assertNotNull($event);
        $this->assertSame('opaque-visitor-1', $event->visitor_id);
        $this->assertSame('session-xyz', $event->session_id);
    }
}
