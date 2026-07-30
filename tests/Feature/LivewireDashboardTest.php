<?php

namespace Lumina\Core\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Lumina\Core\Enums\DeviceType;
use Lumina\Core\Livewire\Dashboard;
use Lumina\Core\Models\Event;
use Lumina\Core\Models\Site;
use Tests\TestCase;

class LivewireDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected Site $site;

    protected function setUp(): void
    {
        parent::setUp();

        $this->site = Site::factory()->create(['domain' => 'test-domain.com']);
    }

    public function test_livewire_dashboard_component_mounts_and_renders_empty_state_when_no_events_exist(): void
    {
        Livewire::test(Dashboard::class, ['site' => $this->site])
            ->assertSee('test-domain.com')
            ->assertSee('No data collected yet')
            ->assertSee('Add the tracking snippet');
    }

    public function test_livewire_dashboard_component_renders_metrics_and_top_pages_when_events_exist(): void
    {
        Event::create([
            'site_id' => $this->site->id,
            'path' => '/home',
            'referrer' => 'https://google.com',
            'visitor_hash' => 'hash_visitor_1',
            'device_type' => DeviceType::Desktop,
            'created_at' => Carbon::now()->subDays(2),
        ]);

        Event::create([
            'site_id' => $this->site->id,
            'path' => '/pricing',
            'referrer' => 'https://twitter.com',
            'visitor_hash' => 'hash_visitor_2',
            'device_type' => DeviceType::Mobile,
            'created_at' => Carbon::now()->subDays(1),
        ]);

        Livewire::test(Dashboard::class, ['site' => $this->site])
            ->assertSee('test-domain.com')
            ->assertDontSee('No data collected yet')
            ->assertSee('Total Pageviews')
            ->assertSee('Unique Visitors')
            ->assertSee('/home')
            ->assertSee('/pricing')
            ->assertSee('https://google.com')
            ->assertSee('https://twitter.com');
    }

    public function test_livewire_dashboard_component_updates_reactively_when_date_period_changes(): void
    {
        Event::create([
            'site_id' => $this->site->id,
            'path' => '/old-page',
            'referrer' => null,
            'visitor_hash' => 'hash_old',
            'device_type' => DeviceType::Desktop,
            'created_at' => Carbon::now()->subDays(10),
        ]);

        Livewire::test(Dashboard::class, ['site' => $this->site, 'period' => '30d'])
            ->assertSee('/old-page')
            ->call('setPeriod', '7d')
            ->assertSet('period', '7d')
            ->assertDontSee('/old-page');
    }
}
