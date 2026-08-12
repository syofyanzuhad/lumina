<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\RateLimiter;
use Lumina\Core\Enums\DeviceType;
use Lumina\Core\Jobs\InsertEvent;
use Lumina\Core\Models\Site;
use Tests\TestCase;

class CollectEndpointTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Site $site;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->site = Site::factory()->create([
            'owner_id' => $this->user->id,
            'domain' => 'example.com',
        ]);
    }

    public function test_valid_pageview_event_dispatches_insert_event_job(): void
    {
        Queue::fake();

        $response = $this->postJson('/api/collect', [
            'domain' => 'example.com',
            'path' => '/pricing',
            'referrer' => 'https://google.com',
            'screen_width' => 1440,
        ]);

        $response->assertStatus(204);

        Queue::assertPushed(InsertEvent::class, function (InsertEvent $job) {
            return $job->siteId === $this->site->id
                && $job->path === '/pricing'
                && $job->referrer === 'https://google.com'
                && $job->deviceType === DeviceType::Desktop
                && strlen($job->visitorHash) === 64;
        });
    }

    public function test_unregistered_domain_is_rejected_with_422(): void
    {
        Queue::fake();

        $response = $this->postJson('/api/collect', [
            'domain' => 'unregistered-site.com',
            'path' => '/home',
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'message' => 'Unregistered domain.',
        ]);

        Queue::assertNothingPushed();
    }

    public function test_missing_required_fields_fails_validation(): void
    {
        Queue::fake();

        $response = $this->postJson('/api/collect', [
            'domain' => 'example.com',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['path']);

        Queue::assertNothingPushed();
    }

    public function test_custom_event_with_metadata_dispatches_insert_event_job(): void
    {
        Queue::fake();

        $response = $this->postJson('/api/collect', [
            'domain' => 'example.com',
            'path' => '/checkout',
            'name' => 'purchase',
            'metadata' => [
                'plan' => 'pro',
                'amount' => 29.99,
            ],
        ]);

        $response->assertStatus(204);

        Queue::assertPushed(InsertEvent::class, function (InsertEvent $job) {
            return $job->siteId === $this->site->id
                && $job->path === '/checkout'
                && is_array($job->metadata)
                && $job->metadata['name'] === 'purchase'
                && $job->metadata['props']['plan'] === 'pro';
        });
    }

    public function test_device_type_derived_from_screen_width(): void
    {
        Queue::fake();

        // Mobile
        $this->postJson('/api/collect', [
            'domain' => 'example.com',
            'path' => '/page1',
            'screen_width' => 414,
        ])->assertStatus(204);

        Queue::assertPushed(InsertEvent::class, function (InsertEvent $job) {
            return $job->deviceType === DeviceType::Mobile;
        });

        // Tablet
        $this->postJson('/api/collect', [
            'domain' => 'example.com',
            'path' => '/page2',
            'screen_width' => 800,
        ])->assertStatus(204);

        Queue::assertPushed(InsertEvent::class, function (InsertEvent $job) {
            return $job->deviceType === DeviceType::Tablet;
        });
    }

    public function test_cors_headers_reflect_requesting_origin(): void
    {
        $response = $this->call('OPTIONS', '/api/collect', [], [], [], [
            'HTTP_ORIGIN' => 'https://syofyanzuhad.dev',
        ]);

        $response->assertStatus(204);
        $response->assertHeader('Access-Control-Allow-Origin', 'https://syofyanzuhad.dev');
        $response->assertHeader('Access-Control-Allow-Credentials', 'true');
    }

    public function test_collect_endpoint_is_rate_limited_per_ip(): void
    {
        Queue::fake();

        // Exhaust the per-IP budget by filling the limiter key up to the cap
        // (attempt() returns false once attempts >= max).
        $key = 'lumina_collect:127.0.0.1';
        RateLimiter::clear($key);
        for ($i = 0; $i < 120; $i++) {
            RateLimiter::hit($key, 60);
        }

        $this->postJson('/api/collect', [
            'domain' => 'example.com',
            'path' => '/page1',
        ])->assertStatus(204); // silently swallowed, never a hard error

        // No job is pushed while the IP is over its budget.
        Queue::assertNothingPushed();

        // A different IP is not affected.
        $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.99'])->postJson('/api/collect', [
            'domain' => 'example.com',
            'path' => '/page2',
        ])->assertStatus(204);

        Queue::assertPushed(InsertEvent::class, 1);
    }
}
