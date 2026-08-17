<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Queue;
use Lumina\Core\Jobs\InsertEvent;
use Lumina\Core\Middleware\TrackPageview;
use Lumina\Core\Models\Site;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

test('package middleware handle passes request to next closure', function () {
    $middleware = new TrackPageview;
    $request = Request::create('/test-page', 'GET');

    $response = $middleware->handle($request, function ($req) {
        return new Response('OK');
    });

    $this->assertEquals('OK', $response->getContent());
});

test('package middleware terminate dispatches insert event job for known site', function () {
    Queue::fake();

    $site = Site::factory()->create(['domain' => 'localhost']);
    $middleware = new TrackPageview;
    $request = Request::create('http://localhost/analytics', 'GET');
    $response = new Response;

    $middleware->terminate($request, $response);

    Queue::assertPushed(InsertEvent::class);
});
