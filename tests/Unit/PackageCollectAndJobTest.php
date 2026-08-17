<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Queue;
use Lumina\Core\Enums\DeviceType;
use Lumina\Core\Http\Controllers\CollectController;
use Lumina\Core\Jobs\InsertEvent;
use Lumina\Core\Models\Site;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

test('package collect controller handles options cors preflight', function () {
    $controller = new CollectController;
    $request = Request::create('/api/collect', 'OPTIONS');
    $request->headers->set('Origin', 'https://example.com');

    $response = $controller($request);

    $this->assertEquals(204, $response->getStatusCode());
    $this->assertEquals('https://example.com', $response->headers->get('Access-Control-Allow-Origin'));
});

test('package collect controller handles get status check', function () {
    $controller = new CollectController;
    $request = Request::create('/api/collect', 'GET');

    $response = $controller($request);

    $this->assertEquals(200, $response->getStatusCode());
    $this->assertStringContainsString('Collector API is active', (string) $response->getContent());
});

test('package collect controller dispatches insert event job', function () {
    Queue::fake();

    $site = Site::factory()->create(['domain' => 'my-domain.com']);
    $controller = new CollectController;

    $request = Request::create('/api/collect', 'POST', [
        'domain' => 'my-domain.com',
        'path' => '/blog/article',
        'screen_width' => 1440,
        'name' => 'button_click',
        'metadata' => ['button_id' => 'signup'],
    ]);

    $response = $controller($request);

    $this->assertEquals(204, $response->getStatusCode());
    Queue::assertPushed(InsertEvent::class);
});

test('package insert event job handles event insertion', function () {
    $site = Site::factory()->create();

    $job = new InsertEvent(
        siteId: $site->id,
        path: '/landing?utm_source=twitter&utm_campaign=launch',
        referrer: 'https://t.co',
        visitorHash: 'hash_123',
        deviceType: DeviceType::Desktop,
        country: 'US',
        metadata: ['plan' => 'pro'],
        userAgent: 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7)',
        ip: '8.8.8.8'
    );

    $job->handle();

    $this->assertDatabaseHas('events', [
        'site_id' => $site->id,
        'path' => '/landing?utm_source=twitter&utm_campaign=launch',
        'clean_path' => '/landing',
        'utm_source' => 'twitter',
        'utm_campaign' => 'launch',
        'country' => 'US',
    ]);
});
