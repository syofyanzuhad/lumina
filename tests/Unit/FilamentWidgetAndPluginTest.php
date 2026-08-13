<?php

namespace Tests\Unit;

use Filament\Panel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Lumina\Core\Filament\LuminaPlugin;
use Lumina\Core\Filament\Resources\SiteResource;
use Lumina\Core\Filament\Widgets\LuminaOverviewWidget;
use Lumina\Core\Models\Site;
use Tests\TestCase;

class FilamentWidgetAndPluginTest extends TestCase
{
    use RefreshDatabase;

    public function test_lumina_plugin_instantiates_and_returns_id(): void
    {
        $plugin = LuminaPlugin::make();
        $this->assertEquals('lumina-core', $plugin->getId());

        $panel = new Panel;
        $plugin->register($panel);
        $plugin->boot($panel);

        $this->assertContains(SiteResource::class, $panel->getResources());
    }

    public function test_lumina_overview_widget_renders_stats_empty_and_with_sites(): void
    {
        $widget = new class extends LuminaOverviewWidget
        {
            public function testGetStats(): array
            {
                return $this->getStats();
            }
        };

        // Empty state when no site registered
        $emptyStats = $widget->testGetStats();
        $this->assertNotEmpty($emptyStats);

        // State with site
        $site = Site::factory()->create();
        $widget->site = $site;
        $stats = $widget->testGetStats();

        $this->assertCount(4, $stats);
    }
}
