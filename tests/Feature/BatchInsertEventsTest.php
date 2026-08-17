<?php

use Illuminate\Support\Facades\DB;
use Lumina\Core\Jobs\BatchInsertEvents;
use Lumina\Core\Jobs\InsertEvent;
use Lumina\Core\Models\Site;

test('BatchInsertEvents inserts multiple events and upserts visitor stats in a single transaction', function () {
    $site = Site::factory()->create();
    $now = now()->toDateTimeString();

    $events = [
        [
            'site_id' => $site->id,
            'path' => '/home',
            'clean_path' => '/home',
            'visitor_hash' => 'hash_v1',
            'visitor_id' => 'vis_1',
            'session_id' => 'sess_1',
            'device_type' => 'desktop',
            'created_at' => $now,
        ],
        [
            'site_id' => $site->id,
            'path' => '/pricing',
            'clean_path' => '/pricing',
            'visitor_hash' => 'hash_v1',
            'visitor_id' => 'vis_1',
            'session_id' => 'sess_1',
            'device_type' => 'desktop',
            'created_at' => $now,
        ],
        [
            'site_id' => $site->id,
            'path' => '/docs',
            'clean_path' => '/docs',
            'visitor_hash' => 'hash_v2',
            'visitor_id' => 'vis_2',
            'session_id' => 'sess_2',
            'device_type' => 'mobile',
            'created_at' => $now,
        ],
    ];

    $job = new BatchInsertEvents($events);
    $job->handle();

    // Assert events were inserted
    expect(DB::table('events')->where('site_id', $site->id)->count())->toBe(3);

    // Assert aggregate stats updated correctly
    $stat1 = DB::table('daily_visitor_stats')
        ->where('site_id', $site->id)
        ->where('visitor_hash', 'vis_1')
        ->first();

    expect($stat1)->not()->toBeNull();
    expect($stat1->views)->toBe(2);

    $stat2 = DB::table('daily_visitor_stats')
        ->where('site_id', $site->id)
        ->where('visitor_hash', 'vis_2')
        ->first();

    expect($stat2)->not()->toBeNull();
    expect($stat2->views)->toBe(1);
});

test('BatchInsertEvents is significantly faster than sequential InsertEvent execution', function () {
    $site = Site::factory()->create();
    $now = now()->toDateTimeString();

    $eventCount = 1000;
    $eventsPayload = [];

    for ($i = 0; $i < $eventCount; $i++) {
        $eventsPayload[] = [
            'site_id' => $site->id,
            'path' => '/page-'.($i % 10),
            'clean_path' => '/page-'.($i % 10),
            'visitor_hash' => md5('vis_'.($i % 100)),
            'visitor_id' => 'vis_'.($i % 100),
            'session_id' => 'sess_'.($i % 150),
            'device_type' => 'desktop',
            'created_at' => $now,
        ];
    }

    // 1. Measure Sequential Processing Time (Individual Jobs)
    $singleStart = microtime(true);
    foreach ($eventsPayload as $payload) {
        $job = new InsertEvent(
            siteId: $payload['site_id'],
            path: $payload['path'],
            referrer: null,
            visitorHash: $payload['visitor_hash'],
            deviceType: $payload['device_type'],
            visitorId: $payload['visitor_id'],
            sessionId: $payload['session_id']
        );
        $job->handle();
    }
    $singleDuration = round((microtime(true) - $singleStart) * 1000, 2);

    // Clear events database for second run
    DB::table('events')->where('site_id', $site->id)->delete();
    DB::table('daily_visitor_stats')->where('site_id', $site->id)->delete();

    // 2. Measure Batch Processing Time (Single Batch Job)
    $batchStart = microtime(true);
    $batchJob = new BatchInsertEvents($eventsPayload);
    $batchJob->handle();
    $batchDuration = round((microtime(true) - $batchStart) * 1000, 2);

    // Calculate Speedup Multiplier
    $speedupRatio = round($singleDuration / max($batchDuration, 0.01), 1);

    // Assert batch is faster than sequential single execution
    expect($batchDuration)->toBeLessThan($singleDuration);
    expect(DB::table('events')->where('site_id', $site->id)->count())->toBe($eventCount);
});

test('BatchInsertEvents ingests 1,000,000 events in realistic chunk sizes', function () {
    $site = Site::factory()->create();
    $now = now()->toDateTimeString();

    // 1,000 rows per chunk keeps a single multi-row INSERT below SQLite's
    // variable limit while amortizing transaction overhead.
    $chunkSize = 1000;
    $total = 1_000_000;

    $start = microtime(true);
    $payload = [];
    for ($i = 0; $i < $total; $i++) {
        $payload[] = [
            'site_id' => $site->id,
            'path' => '/page-'.($i % 10),
            'clean_path' => '/page-'.($i % 10),
            'visitor_hash' => md5('vis_'.($i % 10000)),
            'visitor_id' => 'vis_'.($i % 10000),
            'session_id' => 'sess_'.($i % 15000),
            'device_type' => 'desktop',
            'created_at' => $now,
        ];

        if (count($payload) >= $chunkSize) {
            (new BatchInsertEvents($payload))->handle();
            $payload = [];
        }
    }

    if (! empty($payload)) {
        (new BatchInsertEvents($payload))->handle();
    }
    $duration = round(microtime(true) - $start, 2);

    // All events landed and visitor stats were upserted along the way
    // (10,000 distinct visitors, each counted across 100 events).
    expect(DB::table('events')->where('site_id', $site->id)->count())->toBe($total);
    expect(DB::table('daily_visitor_stats')->where('site_id', $site->id)->count())->toBe(10000);

    // 1M events through the batch ingest path should complete within reasonable benchmark headroom.
    expect($duration)->toBeLessThan(150);
})->skip(fn () => getenv('CI') === 'true' || getenv('GITHUB_ACTIONS') === 'true', 'Skipping 1M events benchmark on CI to optimize pipeline duration.');
