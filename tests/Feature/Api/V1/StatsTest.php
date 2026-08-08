<?php

use Lumina\Core\Models\Event;
use Lumina\Core\Models\Site;

test('stats api requires authentication token', function () {
    $response = $this->getJson('/api/v1/stats');

    $response->assertStatus(401)
        ->assertJsonPath('error', 'Unauthorized');
});

test('stats api validates api token and returns overview', function () {
    $site = Site::factory()->create([
        'api_token' => 'lum_testtoken12345678901234567890123456789012345678901234567890',
    ]);

    Event::create([
        'site_id' => $site->id,
        'path' => '/features?utm_source=twitter&utm_medium=social&utm_campaign=summer_sale',
        'clean_path' => '/features',
        'visitor_hash' => 'hash123',
        'utm_source' => 'twitter',
        'utm_medium' => 'social',
        'utm_campaign' => 'summer_sale',
    ]);

    $response = $this->getJson('/api/v1/stats', [
        'X-API-Key' => $site->api_token,
    ]);

    $response->assertStatus(200)
        ->assertJsonPath('site', $site->domain)
        ->assertJsonPath('data.total_pageviews', 1);
});

test('stats api supports filtering by type for utm campaigns and top pages', function () {
    $site = Site::factory()->create([
        'api_token' => 'lum_testtoken99999999999999999999999999999999999999999999999999',
    ]);

    Event::create([
        'site_id' => $site->id,
        'path' => '/pricing?utm_source=newsletter&utm_medium=email&utm_campaign=launch',
        'clean_path' => '/pricing',
        'visitor_hash' => 'hash456',
        'utm_source' => 'newsletter',
        'utm_medium' => 'email',
        'utm_campaign' => 'launch',
    ]);

    $response = $this->getJson('/api/v1/stats?type=utm-campaigns', [
        'Authorization' => 'Bearer '.$site->api_token,
    ]);

    $response->assertStatus(200)
        ->assertJsonPath('site', $site->domain)
        ->assertJsonPath('utm_campaigns.0.campaign', 'launch');
});
