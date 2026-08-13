<?php

namespace Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Queue;
use Lumina\Core\Jobs\InsertEvent;
use Lumina\Core\Middleware\TrackPageview;
use Lumina\Core\Models\Site;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class PackageMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    public function test_package_middleware_handle_passes_request_to_next_closure(): void
    {
        $middleware = new TrackPageview;
        $request = Request::create('/test-page', 'GET');

        $response = $middleware->handle($request, function ($req) {
            return new Response('OK');
        });

        $this->assertEquals('OK', $response->getContent());
    }

    public function test_package_middleware_terminate_dispatches_insert_event_job_for_known_site(): void
    {
        Queue::fake();

        $site = Site::factory()->create(['domain' => 'localhost']);
        $middleware = new TrackPageview;
        $request = Request::create('http://localhost/analytics', 'GET');
        $response = new Response;

        $middleware->terminate($request, $response);

        Queue::assertPushed(InsertEvent::class);
    }
}
