<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Lumina\Core\Enums\DeviceType;
use Lumina\Core\Models\Event;
use Lumina\Core\Models\Site;
use Lumina\Core\Services\AnalyticsService;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    $this->service = new AnalyticsService;

    $this->siteA = Site::factory()->create(['domain' => 'site-a.com']);
    $this->siteB = Site::factory()->create(['domain' => 'site-b.com']);

    $this->startDate = Carbon::parse('2026-07-01 00:00:00');
    $this->endDate = Carbon::parse('2026-07-03 23:59:59');

    // Seed events for Site A
    // Day 1: 3 events (2 unique visitors, 2 for /home, 1 for /pricing)
    Event::create([
        'site_id' => $this->siteA->id,
        'path' => '/home',
        'referrer' => 'https://google.com',
        'visitor_hash' => 'hash_visitor_1',
        'device_type' => DeviceType::Desktop,
        'created_at' => Carbon::parse('2026-07-01 10:00:00'),
    ]);
    Event::create([
        'site_id' => $this->siteA->id,
        'path' => '/home',
        'referrer' => 'https://google.com',
        'visitor_hash' => 'hash_visitor_1',
        'device_type' => DeviceType::Desktop,
        'created_at' => Carbon::parse('2026-07-01 11:00:00'),
    ]);
    Event::create([
        'site_id' => $this->siteA->id,
        'path' => '/pricing',
        'referrer' => 'https://twitter.com',
        'visitor_hash' => 'hash_visitor_2',
        'device_type' => DeviceType::Mobile,
        'created_at' => Carbon::parse('2026-07-01 12:00:00'),
    ]);

    // Day 2: 2 events for Site A (1 custom event)
    Event::create([
        'site_id' => $this->siteA->id,
        'path' => '/checkout',
        'referrer' => 'https://google.com',
        'visitor_hash' => 'hash_visitor_3',
        'device_type' => DeviceType::Desktop,
        'metadata' => ['name' => 'purchase_click', 'props' => ['plan' => 'pro']],
        'created_at' => Carbon::parse('2026-07-02 14:00:00'),
    ]);
    Event::create([
        'site_id' => $this->siteA->id,
        'path' => '/checkout',
        'referrer' => null,
        'visitor_hash' => 'hash_visitor_3',
        'device_type' => DeviceType::Desktop,
        'metadata' => ['name' => 'purchase_click', 'props' => ['plan' => 'enterprise']],
        'created_at' => Carbon::parse('2026-07-02 15:00:00'),
    ]);

    // Out-of-bounds event for Site A (July 10)
    Event::create([
        'site_id' => $this->siteA->id,
        'path' => '/out-of-bounds',
        'referrer' => null,
        'visitor_hash' => 'hash_visitor_99',
        'device_type' => DeviceType::Desktop,
        'created_at' => Carbon::parse('2026-07-10 10:00:00'),
    ]);

    // Event for Site B (should be ignored)
    Event::create([
        'site_id' => $this->siteB->id,
        'path' => '/other-site',
        'referrer' => null,
        'visitor_hash' => 'hash_visitor_site_b',
        'device_type' => DeviceType::Desktop,
        'created_at' => Carbon::parse('2026-07-01 10:00:00'),
    ]);
});

test('it calculates total pageviews correctly for site and date range', function () {
    $pageviews = $this->service->getPageviews($this->siteA, $this->startDate, $this->endDate);

    expect($pageviews)->toBe(5);
});

test('it calculates unique visitors correctly', function () {
    $visitors = $this->service->getUniqueVisitors($this->siteA, $this->startDate, $this->endDate);

    // hash_visitor_1, hash_visitor_2, hash_visitor_3 = 3 unique visitors
    expect($visitors)->toBe(3);
});

test('it calculates top pages with count and percentage', function () {
    $topPages = $this->service->getTopPages($this->siteA, $this->startDate, $this->endDate);

    expect($topPages)->toHaveCount(3);
    expect($topPages->first())->toBe([
        'path' => '/checkout',
        'count' => 2,
        'percentage' => 40.0,
    ]);
});

test('it calculates top referrers with count and percentage', function () {
    $topReferrers = $this->service->getTopReferrers($this->siteA, $this->startDate, $this->endDate);

    expect($topReferrers)->toHaveCount(2);
    expect($topReferrers->first())->toBe([
        'referrer' => 'https://google.com',
        'count' => 3,
        'percentage' => 60.0,
    ]);
});

test('it generates daily pageview timeseries for date range', function () {
    $daily = $this->service->getDailyPageviews($this->siteA, $this->startDate, $this->endDate);

    expect($daily)->toHaveCount(3);
    expect($daily[0])->toBe([
        'date' => '2026-07-01',
        'pageviews' => 3,
        'visitors' => 2,
    ]);
    expect($daily[1])->toBe([
        'date' => '2026-07-02',
        'pageviews' => 2,
        'visitors' => 1,
    ]);
    expect($daily[2])->toBe([
        'date' => '2026-07-03',
        'pageviews' => 0,
        'visitors' => 0,
    ]);
});

test('it aggregates custom events from metadata column', function () {
    $customEvents = $this->service->getCustomEvents($this->siteA, $this->startDate, $this->endDate);

    expect($customEvents)->toHaveCount(1);
    expect($customEvents->first())->toBe([
        'name' => 'purchase_click',
        'count' => 2,
    ]);
});

test('it returns complete dashboard overview payload', function () {
    $overview = $this->service->getOverview($this->siteA, $this->startDate, $this->endDate);

    expect($overview)->toHaveKeys([
        'total_pageviews',
        'unique_visitors',
        'top_pages',
        'top_referrers',
        'daily_pageviews',
        'custom_events',
    ]);
    expect($overview['total_pageviews'])->toBe(5);
    expect($overview['unique_visitors'])->toBe(3);
});

test('it caches aggregation queries for 60 seconds', function () {
    // First call populates cache
    $firstCall = $this->service->getPageviews($this->siteA, $this->startDate, $this->endDate);
    expect($firstCall)->toBe(5);

    // Add extra event directly to database
    Event::create([
        'site_id' => $this->siteA->id,
        'path' => '/new-event',
        'referrer' => null,
        'visitor_hash' => 'hash_new',
        'device_type' => DeviceType::Desktop,
        'created_at' => Carbon::parse('2026-07-01 15:00:00'),
    ]);

    // Second call reads cached value (still 5)
    $secondCall = $this->service->getPageviews($this->siteA, $this->startDate, $this->endDate);
    expect($secondCall)->toBe(5);

    // Clearing cache retrieves fresh count (6)
    Cache::flush();
    $thirdCall = $this->service->getPageviews($this->siteA, $this->startDate, $this->endDate);
    expect($thirdCall)->toBe(6);
});
