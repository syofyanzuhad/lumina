<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Lumina\Core\Jobs\InsertEvent;
use Lumina\Core\Models\Event;
use Lumina\Core\Models\Site;
use Lumina\Core\Services\AnalyticsService;
use Lumina\Core\Support\CountryHelper;
use Tests\TestCase;

class DetectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_country_helper_resolves_common_country_codes(): void
    {
        $this->assertEquals('United States', CountryHelper::getName('US'));
        $this->assertEquals('United Kingdom', CountryHelper::getName('gb'));
        $this->assertEquals('Indonesia', CountryHelper::getName('ID'));
        $this->assertNull(CountryHelper::getName(null));
    }

    public function test_insert_event_job_parses_user_agent_and_resolves_detection_fields(): void
    {
        $site = Site::factory()->create(['domain' => 'example.com']);
        $ua = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36';

        InsertEvent::dispatchSync(
            siteId: $site->id,
            path: '/test-page',
            referrer: 'https://google.com',
            visitorHash: 'hash123',
            deviceType: 'desktop',
            country: 'US',
            metadata: null,
            userAgent: $ua,
            ip: '8.8.8.8'
        );

        $event = Event::first();

        $this->assertNotNull($event);
        $this->assertEquals('Chrome', $event->browser);
        $this->assertStringStartsWith('120', $event->browser_version);
        $this->assertEquals('OS X', $event->os);
        $this->assertEquals('US', $event->country_code);
        $this->assertEquals('United States', $event->country_name);
    }

    public function test_analytics_service_returns_top_browsers_os_and_countries(): void
    {
        $site = Site::factory()->create(['domain' => 'example.com']);

        Event::factory()->count(3)->create([
            'site_id' => $site->id,
            'browser' => 'Chrome',
            'os' => 'macOS',
            'country_code' => 'US',
            'country_name' => 'United States',
            'created_at' => now(),
        ]);

        Event::factory()->count(2)->create([
            'site_id' => $site->id,
            'browser' => 'Firefox',
            'os' => 'Windows',
            'country_code' => 'GB',
            'country_name' => 'United Kingdom',
            'created_at' => now(),
        ]);

        $service = new AnalyticsService;
        $start = now()->subDays(7);
        $end = now();

        $browsers = $service->getTopBrowsers($site, $start, $end);
        $os = $service->getTopOperatingSystems($site, $start, $end);
        $countries = $service->getTopCountries($site, $start, $end);
        $overview = $service->getOverview($site, $start, $end);

        $this->assertCount(2, $browsers);
        $this->assertEquals('Chrome', $browsers->first()['browser']);
        $this->assertEquals(3, $browsers->first()['count']);

        $this->assertCount(2, $os);
        $this->assertEquals('macOS', $os->first()['os']);
        $this->assertEquals(3, $os->first()['count']);

        $this->assertCount(2, $countries);
        $this->assertEquals('US', $countries->first()['code']);
        $this->assertEquals('United States', $countries->first()['name']);
        $this->assertEquals(3, $countries->first()['count']);

        $this->assertArrayHasKey('top_browsers', $overview);
        $this->assertArrayHasKey('top_os', $overview);
        $this->assertArrayHasKey('top_countries', $overview);
    }
}
