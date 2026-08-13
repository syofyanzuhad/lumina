<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Lumina\Core\Enums\DeviceType;
use Lumina\Core\Models\Event;
use Lumina\Core\Models\Site;

uses(RefreshDatabase::class);

test('lumina:backfill-clean-path backfills clean_path column on events', function () {
    $site = Site::factory()->create();

    Event::create([
        'site_id' => $site->id,
        'path' => '/search?q=test',
        'clean_path' => null,
        'visitor_hash' => 'hash_1',
        'device_type' => DeviceType::Desktop,
    ]);

    $this->artisan('lumina:backfill-clean-path')
        ->expectsOutput('Starting backfill for clean_path...')
        ->expectsOutput('Backfill completed successfully!')
        ->assertExitCode(0);

    $this->assertDatabaseHas('events', [
        'path' => '/search?q=test',
        'clean_path' => '/search',
    ]);
});

test('lumina:backfill-visitor-stats backfills daily visitor stats table', function () {
    $site = Site::factory()->create();

    Event::create([
        'site_id' => $site->id,
        'path' => '/page',
        'clean_path' => '/page',
        'visitor_hash' => 'hash_2',
        'device_type' => DeviceType::Desktop,
        'created_at' => now(),
    ]);

    $this->artisan('lumina:backfill-visitor-stats')
        ->expectsOutput('Starting backfill for daily_visitor_stats...')
        ->expectsOutput('Backfill completed successfully!')
        ->assertExitCode(0);

    $this->assertDatabaseHas('daily_visitor_stats', [
        'site_id' => $site->id,
        'visitor_hash' => 'hash_2',
        'views' => 1,
    ]);
});
