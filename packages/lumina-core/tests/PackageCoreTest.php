<?php

namespace Lumina\Core\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Lumina\Core\Models\Event;
use Lumina\Core\Models\Site;

class PackageCoreTest extends TestCase
{
    use RefreshDatabase;
    public function test_site_factory_creates_site_model(): void
    {
        $site = Site::factory()->create();

        $this->assertInstanceOf(Site::class, $site);
        $this->assertNotNull($site->id);
    }

    public function test_event_factory_creates_event_model(): void
    {
        $event = Event::factory()->create();

        $this->assertInstanceOf(Event::class, $event);
        $this->assertNotNull($event->id);
    }
}
