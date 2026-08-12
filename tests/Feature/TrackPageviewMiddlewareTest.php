<?php

use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
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

test('rate limiter never records more hits than the cap under burst load', function () {
    $site = Site::factory()->create(['domain' => 'burst.test']);
    URL::forceRootUrl('http://burst.test');

    Queue::fake();

    // Fire cap+1 effectively-concurrent requests; the atomic attempt() must
    // swallow the excess instead of recording more hits than the cap.
    for ($i = 0; $i < 61; $i++) {
        $this->withHeaders(['Host' => 'burst.test'])->get('/');
    }

    expect(RateLimiter::attempts('lumina_ip:127.0.0.1'))->toBe(60);
    Queue::assertPushed(InsertEvent::class, 60);
});

test('site lookup is cached after the first request', function () {
    $site = Site::factory()->create(['domain' => 'cached.test']);
    URL::forceRootUrl('http://cached.test');

    Queue::fake();

    $this->withHeaders(['Host' => 'cached.test'])->get('/');

    expect(Cache::has('lumina_site_lookup:cached.test'))->toBeTrue();

    // Saving the site must invalidate the cached lookup.
    $site->update(['domain' => 'cached.test']);
    expect(Cache::has('lumina_site_lookup:cached.test'))->toBeFalse();
});

test('ignores proxy country headers unless the request is from a trusted proxy', function () {
    $site = Site::factory()->create(['domain' => 'proxy.test']);
    URL::forceRootUrl('http://proxy.test');

    // No trusted proxies configured => CF-IPCountry is untrusted spoofable input.
    $this->withHeaders([
        'Host' => 'proxy.test',
        'CF-IPCountry' => 'US',
        'X-Vercel-IP-Country' => 'DE',
    ])->get('/');

    $event = Event::first();
    expect($event)->not->toBeNull()
        ->and($event->country)->toBeNull();
});

test('uses the first-party X-Country override regardless of proxy trust', function () {
    $site = Site::factory()->create(['domain' => 'country.test']);
    URL::forceRootUrl('http://country.test');

    $this->withHeaders([
        'Host' => 'country.test',
        'X-Country' => 'ID',
        'CF-IPCountry' => 'US',
    ])->get('/');

    $event = Event::first();
    expect($event->country)->toBe('ID');
});

test('stores client-provided visitor and session identity', function () {
    $site = Site::factory()->create(['domain' => 'identity.test']);
    URL::forceRootUrl('http://identity.test');

    $this->withHeaders([
        'Host' => 'identity.test',
        'X-Lumina-Visitor' => 'opaque-visitor-123',
        'X-Lumina-Session' => 'session-abc',
    ])->get('/');

    $event = Event::first();

    expect($event->visitor_id)->toBe('opaque-visitor-123')
        ->and($event->session_id)->toBe('session-abc')
        ->and($event->visitor_hash)->toBe('opaque-visitor-123');
});

test('derives a stable cross-day visitor hash from ip+ua (no daily salt)', function () {
    $site = Site::factory()->create(['domain' => 'stable.test']);
    URL::forceRootUrl('http://stable.test');

    $ua = 'TestBrowser/1.0';

    // Day 1
    $this->withServerVariables(['REMOTE_ADDR' => '192.168.1.100'])
        ->withHeaders(['Host' => 'stable.test', 'User-Agent' => $ua])
        ->get('/');

    // Day 2: same IP + UA must resolve to the same visitor hash, so cross-day
    // unique visitors stay exact. (Sync queue in beforeEach actually persists
    // the events; the salt is stable, unlike the old daily-rotating salt.)
    Carbon::setTestNow(now()->addDay());
    $this->withServerVariables(['REMOTE_ADDR' => '192.168.1.100'])
        ->withHeaders(['Host' => 'stable.test', 'User-Agent' => $ua])
        ->get('/');
    Carbon::setTestNow();

    $hashes = Event::pluck('visitor_hash')->unique();

    expect($hashes)->toHaveCount(1);
});

test('queue dispatch failure is reported without failing the request', function () {
    $site = Site::factory()->create(['domain' => 'queuedown.test']);
    URL::forceRootUrl('http://queuedown.test');

    Log::spy();

    // Simulate an unavailable queue connection at the dispatcher seam.
    $this->mock(
        Dispatcher::class,
        fn ($mock) => $mock->shouldReceive('dispatch')->andThrow(new RuntimeException('queue down'))
    );

    $response = $this->withHeaders(['Host' => 'queuedown.test'])->get('/');

    $response->assertStatus(200);
    Log::shouldHaveReceived('error')->once();
});
