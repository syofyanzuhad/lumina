<?php

use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;
use Lumina\Core\Enums\DeviceType;
use Lumina\Core\Models\Event;
use Lumina\Core\Models\Site;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->site = Site::factory()->create(['owner_id' => $this->user->id]);
    $this->actingAs($this->user);
});

test('device inclusion filter scopes total pageviews to matching device type', function () {
    Event::factory()->count(3)->create([
        'site_id' => $this->site->id,
        'device_type' => DeviceType::Desktop,
        'created_at' => now()->subDay(),
    ]);
    Event::factory()->count(2)->create([
        'site_id' => $this->site->id,
        'device_type' => DeviceType::Mobile,
        'created_at' => now()->subDay(),
    ]);

    $response = $this->get("/dashboard?site_id={$this->site->id}&device=desktop");

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Dashboard')
        ->where('total_pageviews', 3)
    );
});

test('device exclusion filter scopes total pageviews excluding device type', function () {
    Event::factory()->count(3)->create([
        'site_id' => $this->site->id,
        'device_type' => DeviceType::Desktop,
        'created_at' => now()->subDay(),
    ]);
    Event::factory()->count(2)->create([
        'site_id' => $this->site->id,
        'device_type' => DeviceType::Mobile,
        'created_at' => now()->subDay(),
    ]);

    $response = $this->get("/dashboard?site_id={$this->site->id}&device=!desktop");

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Dashboard')
        ->where('total_pageviews', 2)
    );
});

test('path inclusion filter matches clean_path and path expressions', function () {
    Event::factory()->count(2)->create([
        'site_id' => $this->site->id,
        'path' => '/about?utm=1',
        'clean_path' => '/about',
        'created_at' => now()->subDay(),
    ]);
    Event::factory()->count(1)->create([
        'site_id' => $this->site->id,
        'path' => '/contact',
        'clean_path' => '/contact',
        'created_at' => now()->subDay(),
    ]);

    $response = $this->get("/dashboard?site_id={$this->site->id}&path=/about");

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Dashboard')
        ->where('total_pageviews', 2)
    );
});

test('path exclusion filter excludes matching paths', function () {
    Event::factory()->count(2)->create([
        'site_id' => $this->site->id,
        'path' => '/about',
        'clean_path' => '/about',
        'created_at' => now()->subDay(),
    ]);
    Event::factory()->count(1)->create([
        'site_id' => $this->site->id,
        'path' => '/contact',
        'clean_path' => '/contact',
        'created_at' => now()->subDay(),
    ]);

    $response = $this->get("/dashboard?site_id={$this->site->id}&path=!/about");

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Dashboard')
        ->where('total_pageviews', 1)
    );
});

test('country inclusion filter scopes metrics to matching country_code', function () {
    Event::factory()->count(2)->create([
        'site_id' => $this->site->id,
        'country_code' => 'US',
        'created_at' => now()->subDay(),
    ]);
    Event::factory()->count(1)->create([
        'site_id' => $this->site->id,
        'country_code' => 'DE',
        'created_at' => now()->subDay(),
    ]);

    $response = $this->get("/dashboard?site_id={$this->site->id}&country=US");

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Dashboard')
        ->where('total_pageviews', 2)
    );

    // Filter using full English name
    $this->get("/dashboard?site_id={$this->site->id}&country=United States")
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('total_pageviews', 2));

    // Exclude using full English name
    $this->get("/dashboard?site_id={$this->site->id}&country=!United States")
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('total_pageviews', 1));
});

test('browser inclusion filter scopes metrics to matching browser', function () {
    Event::factory()->count(2)->create([
        'site_id' => $this->site->id,
        'browser' => 'Chrome',
        'created_at' => now()->subDay(),
    ]);
    Event::factory()->count(1)->create([
        'site_id' => $this->site->id,
        'browser' => 'Firefox',
        'created_at' => now()->subDay(),
    ]);

    $response = $this->get("/dashboard?site_id={$this->site->id}&browser=Chrome");

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Dashboard')
        ->where('total_pageviews', 2)
    );
});

test('os inclusion filter scopes metrics to matching os', function () {
    Event::factory()->count(2)->create([
        'site_id' => $this->site->id,
        'os' => 'macOS',
        'created_at' => now()->subDay(),
    ]);
    Event::factory()->count(1)->create([
        'site_id' => $this->site->id,
        'os' => 'Windows',
        'created_at' => now()->subDay(),
    ]);

    $response = $this->get("/dashboard?site_id={$this->site->id}&os=macOS");

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Dashboard')
        ->where('total_pageviews', 2)
    );
});

test('referrer inclusion filter scopes metrics to matching referrer', function () {
    Event::factory()->count(2)->create([
        'site_id' => $this->site->id,
        'referrer' => 'https://google.com',
        'created_at' => now()->subDay(),
    ]);
    Event::factory()->count(1)->create([
        'site_id' => $this->site->id,
        'referrer' => 'https://twitter.com',
        'created_at' => now()->subDay(),
    ]);

    $response = $this->get("/dashboard?site_id={$this->site->id}&referrer=https://google.com");

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Dashboard')
        ->where('total_pageviews', 2)
    );
});

test('referrer filter matches platform name Google across multiple google domains', function () {
    Event::factory()->create([
        'site_id' => $this->site->id,
        'referrer' => 'https://www.google.com/search?q=laravel',
        'created_at' => now()->subDay(),
    ]);
    Event::factory()->create([
        'site_id' => $this->site->id,
        'referrer' => 'https://google.co.id/',
        'created_at' => now()->subDay(),
    ]);
    Event::factory()->create([
        'site_id' => $this->site->id,
        'referrer' => 'https://t.co/xyz',
        'created_at' => now()->subDay(),
    ]);

    // Inclusion by platform name "Google" matches both google referrers
    $this->get("/dashboard?site_id={$this->site->id}&referrer=Google")
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('total_pageviews', 2));

    // Exclusion by platform name "!Google" leaves only the twitter referrer
    $this->get("/dashboard?site_id={$this->site->id}&referrer=!Google")
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('total_pageviews', 1));
});

test('utm_campaign inclusion filter scopes metrics to matching campaign', function () {
    Event::factory()->count(2)->create([
        'site_id' => $this->site->id,
        'utm_campaign' => 'spring_sale',
        'created_at' => now()->subDay(),
    ]);
    Event::factory()->count(1)->create([
        'site_id' => $this->site->id,
        'utm_campaign' => 'black_friday',
        'created_at' => now()->subDay(),
    ]);

    $response = $this->get("/dashboard?site_id={$this->site->id}&utm_campaign=spring_sale");

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Dashboard')
        ->where('total_pageviews', 2)
    );
});

test('multiple filters combine with AND logic', function () {
    Event::factory()->create([
        'site_id' => $this->site->id,
        'device_type' => DeviceType::Desktop,
        'browser' => 'Chrome',
        'created_at' => now()->subDay(),
    ]);
    Event::factory()->create([
        'site_id' => $this->site->id,
        'device_type' => DeviceType::Desktop,
        'browser' => 'Firefox',
        'created_at' => now()->subDay(),
    ]);
    Event::factory()->create([
        'site_id' => $this->site->id,
        'device_type' => DeviceType::Mobile,
        'browser' => 'Chrome',
        'created_at' => now()->subDay(),
    ]);

    $response = $this->get("/dashboard?site_id={$this->site->id}&device=desktop&browser=Chrome");

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Dashboard')
        ->where('total_pageviews', 1)
    );
});

test('breakdown endpoint applies dimension filters to aggregated counts', function () {
    Event::factory()->count(3)->create([
        'site_id' => $this->site->id,
        'device_type' => DeviceType::Desktop,
        'path' => '/pricing',
        'clean_path' => '/pricing',
        'created_at' => now()->subDay(),
    ]);
    Event::factory()->count(2)->create([
        'site_id' => $this->site->id,
        'device_type' => DeviceType::Mobile,
        'path' => '/pricing',
        'clean_path' => '/pricing',
        'created_at' => now()->subDay(),
    ]);

    $response = $this->getJson(route('dashboard.breakdown', [
        'site_id' => $this->site->id,
        'type' => 'pages',
        'device' => 'desktop',
    ]));

    $response->assertOk();
    $response->assertJsonPath('type', 'pages');
    $response->assertJsonPath('data.0.path', '/pricing');
    $response->assertJsonPath('data.0.count', 3);
});

test('utm_source inclusion and exclusion filter scopes metrics', function () {
    Event::factory()->count(2)->create([
        'site_id' => $this->site->id,
        'utm_source' => 'twitter',
        'created_at' => now()->subDay(),
    ]);
    Event::factory()->count(1)->create([
        'site_id' => $this->site->id,
        'utm_source' => 'facebook',
        'created_at' => now()->subDay(),
    ]);

    $this->get("/dashboard?site_id={$this->site->id}&utm_source=twitter")
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('total_pageviews', 2));

    $this->get("/dashboard?site_id={$this->site->id}&utm_source=!twitter")
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('total_pageviews', 1));
});

test('utm_medium inclusion and exclusion filter scopes metrics', function () {
    Event::factory()->count(2)->create([
        'site_id' => $this->site->id,
        'utm_medium' => 'cpc',
        'created_at' => now()->subDay(),
    ]);
    Event::factory()->count(1)->create([
        'site_id' => $this->site->id,
        'utm_medium' => 'organic',
        'created_at' => now()->subDay(),
    ]);

    $this->get("/dashboard?site_id={$this->site->id}&utm_medium=cpc")
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('total_pageviews', 2));

    $this->get("/dashboard?site_id={$this->site->id}&utm_medium=!cpc")
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('total_pageviews', 1));
});
