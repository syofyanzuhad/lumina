<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Lumina\Core\Enums\DeviceType;
use Lumina\Core\Jobs\InsertEvent;
use Lumina\Core\Models\Event;
use Lumina\Core\Models\Site;
use Tests\TestCase;

class QueueWorkerIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_queue_worker_processes_insert_event_job_and_persists_to_database(): void
    {
        config(['queue.default' => 'database']);

        $user = User::factory()->create();
        $site = Site::factory()->create([
            'owner_id' => $user->id,
            'domain' => 'example.com',
        ]);

        $this->assertEquals(0, DB::table('jobs')->count());

        $visitorHash = hash('sha256', 'test_visitor_salt');

        InsertEvent::dispatch(
            siteId: $site->id,
            path: '/pricing',
            referrer: 'https://google.com',
            visitorHash: $visitorHash,
            deviceType: DeviceType::Desktop,
            country: 'ID',
            metadata: ['plan' => 'pro'],
        );

        $this->assertEquals(1, DB::table('jobs')->count());
        $this->assertDatabaseCount('events', 0);

        Artisan::call('queue:work', [
            'connection' => 'database',
            '--once' => true,
        ]);

        $this->assertEquals(0, DB::table('jobs')->count());
        $this->assertDatabaseCount('events', 1);

        $event = Event::first();
        $this->assertNotNull($event);
        $this->assertEquals($site->id, $event->site_id);
        $this->assertEquals('/pricing', $event->path);
        $this->assertEquals('https://google.com', $event->referrer);
        $this->assertEquals($visitorHash, $event->visitor_hash);
        $this->assertEquals(DeviceType::Desktop, $event->device_type);
        $this->assertEquals('ID', $event->country);
        $this->assertEquals(['plan' => 'pro'], $event->metadata);
    }
}
