<?php

use App\Models\User;
use Lumina\Core\Enums\DeviceType;
use Lumina\Core\Models\Event;
use Lumina\Core\Models\Site;

test('owner can view site show page and api_token is generated if missing', function () {
    $user = User::factory()->create();
    $site = Site::factory()->create(['owner_id' => $user->id, 'api_token' => null]);

    $response = $this->actingAs($user)->get(route('sites.show', $site));

    $response->assertOk();
    expect($site->fresh()->api_token)->not()->toBeNull();
});

test('non-owner cannot view site show page', function () {
    $owner = User::factory()->create();
    $otherUser = User::factory()->create();
    $site = Site::factory()->create(['owner_id' => $owner->id]);

    $response = $this->actingAs($otherUser)->get(route('sites.show', $site));

    $response->assertForbidden();
});

test('owner can delete site and is redirected to sites index', function () {
    $user = User::factory()->create();
    $site = Site::factory()->create(['owner_id' => $user->id]);

    $response = $this->actingAs($user)->delete(route('sites.destroy', $site));

    $response->assertRedirect(route('sites.index'));
    $this->assertDatabaseMissing('sites', ['id' => $site->id]);
});

test('owner can export site events CSV with event rows', function () {
    $user = User::factory()->create();
    $site = Site::factory()->create(['owner_id' => $user->id]);

    Event::create([
        'site_id' => $site->id,
        'path' => '/test-export',
        'referrer' => 'https://google.com',
        'visitor_hash' => 'hash_export',
        'device_type' => DeviceType::Desktop,
    ]);

    $response = $this->actingAs($user)->get(route('sites.export', $site));

    $response->assertOk()
        ->assertHeader('Content-Type', 'text/csv; charset=utf-8');

    ob_start();
    $response->sendContent();
    $content = ob_get_clean();

    $this->assertStringContainsString('/test-export', (string) $content);
});
