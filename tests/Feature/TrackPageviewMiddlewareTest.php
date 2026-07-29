<?php

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Lumina\Core\Enums\DeviceType;
use Lumina\Core\Jobs\InsertEvent;
use Lumina\Core\Models\Event;
use Lumina\Core\Models\Site;

beforeEach(function () {
    config(['queue.default' => 'sync']);
    Cache::flush();
    RateLimiter::clear('lumina_ip:127.0.0.1');
    RateLimiter::clear('lumina_ip:192.168.1.100');
    RateLimiter::clear('lumina_site:example.test');
    RateLimiter::clear('lumina_site:rate-limited.test');
});

test('it inserts event into database when tracked', function () {
    $site = Site::factory()->create(['domain' => 'example.test']);
    URL::forceRootUrl('http://example.test');

    $response = $this->withHeaders([
        'Host' => 'example.test',
        'User-Agent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 16_5 like Mac OS X)',
        'X-Country' => 'ID',
    ])->get('/');

    $response->assertStatus(200);

    $this->assertDatabaseHas('events', [
        'site_id' => $site->id,
        'path' => '/',
        'device_type' => DeviceType::Mobile->value,
        'country' => 'ID',
    ]);
});

test('it hashes visitor IP with daily salt and does not store raw IP', function () {
    $site = Site::factory()->create(['domain' => 'example.test']);
    URL::forceRootUrl('http://example.test');

    $this->withServerVariables(['REMOTE_ADDR' => '192.168.1.100'])
        ->withHeaders([
            'Host' => 'example.test',
            'User-Agent' => 'TestBrowser/1.0',
        ])
        ->get('/');

    $event = Event::first();

    expect($event)->not->toBeNull()
        ->and($event->visitor_hash)->not->toBe('192.168.1.100')
        ->and(strlen($event->visitor_hash))->toBe(64);
});

test('InsertEvent job creates event with metadata array', function () {
    $site = Site::factory()->create();

    InsertEvent::dispatchSync(
        siteId: $site->id,
        path: '/test-path',
        referrer: 'https://google.com',
        visitorHash: str_repeat('a', 64),
        deviceType: DeviceType::Desktop,
        country: 'US',
        metadata: ['utm_source' => 'newsletter', 'page' => 2],
    );

    $event = Event::first();

    expect($event->metadata)->toEqualCanonicalizing(['utm_source' => 'newsletter', 'page' => 2]);
});

test('it dispatches InsertEvent job on valid registered host request', function () {
    Queue::fake();

    $site = Site::factory()->create(['domain' => 'example.test']);
    URL::forceRootUrl('http://example.test');

    $response = $this->withHeaders(['Host' => 'example.test'])->get('/');

    $response->assertStatus(200);

    Queue::assertPushed(InsertEvent::class, function (InsertEvent $job) use ($site) {
        return $job->siteId === $site->id && $job->path === '/';
    });
});

test('it bypasses tracking for unregistered domain host', function () {
    Queue::fake();

    URL::forceRootUrl('http://unregistered-domain.test');

    $response = $this->withHeaders(['Host' => 'unregistered-domain.test'])->get('/');

    $response->assertStatus(200);

    Queue::assertNotPushed(InsertEvent::class);
    $this->assertDatabaseCount('events', 0);
});

test('it silently swallows request and skips tracking when IP rate limited', function () {
    $site = Site::factory()->create(['domain' => 'example.test']);
    URL::forceRootUrl('http://example.test');

    $ipKey = 'lumina_ip:127.0.0.1';
    for ($i = 0; $i < 60; $i++) {
        RateLimiter::hit($ipKey, 60);
    }

    Queue::fake();

    $response = $this->withHeaders(['Host' => 'example.test'])->get('/');

    $response->assertStatus(200);
    Queue::assertNotPushed(InsertEvent::class);
});

test('it silently swallows request and skips tracking when site rate limited', function () {
    $site = Site::factory()->create(['domain' => 'rate-limited.test']);
    URL::forceRootUrl('http://rate-limited.test');

    $siteKey = 'lumina_site:rate-limited.test';
    for ($i = 0; $i < 300; $i++) {
        RateLimiter::hit($siteKey, 300);
    }

    Queue::fake();

    $response = $this->withHeaders(['Host' => 'rate-limited.test'])->get('/');

    $response->assertStatus(200);
    Queue::assertNotPushed(InsertEvent::class);
});
