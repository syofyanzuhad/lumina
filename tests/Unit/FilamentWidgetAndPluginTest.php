<?php

use Filament\Panel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Lumina\Core\Filament\LuminaPlugin;
use Lumina\Core\Filament\Resources\SiteResource;
use Lumina\Core\Filament\Widgets\LuminaOverviewWidget;
use Lumina\Core\Models\Site;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

test('lumina plugin instantiates and returns id', function () {
    $plugin = LuminaPlugin::make();
    $this->assertEquals('lumina-core', $plugin->getId());

    $panel = new Panel;
    $plugin->register($panel);
    $plugin->boot($panel);

    $this->assertContains(SiteResource::class, $panel->getResources());
});

test('lumina overview widget renders stats empty and with sites', function () {
    $widget = new class extends LuminaOverviewWidget
    {
        public function get_stats(): array
        {
            return $this->getStats();
        }
    };

    // Empty state when no site registered
    $emptyStats = $widget->get_stats();
    $this->assertNotEmpty($emptyStats);

    // State with site
    $site = Site::factory()->create();
    $widget->site = $site;
    $stats = $widget->get_stats();

    $this->assertCount(4, $stats);
});
