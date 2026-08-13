<?php

use Lumina\Core\Models\Site;

test('api v1 stats requires authentication via header, bearer or query param', function () {
    $response = $this->getJson('/api/v1/stats');
    $response->assertUnauthorized()
        ->assertJson(['error' => 'Unauthorized']);
});

test('api v1 stats rejects invalid api token', function () {
    $response = $this->withHeader('X-API-Key', 'invalid-token')->getJson('/api/v1/stats');
    $response->assertUnauthorized()
        ->assertJson(['message' => 'Invalid API token.']);
});

test('api v1 stats supports pageviews, top-pages, top-referrers, utm-campaigns type query filters', function () {
    $site = Site::factory()->create(['api_token' => 'valid-api-token-123']);

    // Pageviews type via Bearer token
    $this->withToken('valid-api-token-123')
        ->getJson('/api/v1/stats?type=pageviews')
        ->assertOk()
        ->assertJsonStructure(['site', 'period', 'total_pageviews', 'unique_visitors']);

    // Top pages type via X-API-Key header
    $this->withHeader('X-API-Key', 'valid-api-token-123')
        ->getJson('/api/v1/stats?type=top-pages&period=7d')
        ->assertOk()
        ->assertJsonStructure(['site', 'period', 'top_pages']);

    // Top referrers type via query param
    $this->getJson('/api/v1/stats?api_token=valid-api-token-123&type=top-referrers&period=today')
        ->assertOk()
        ->assertJsonStructure(['site', 'period', 'top_referrers']);

    // UTM campaigns type with custom date range
    $this->getJson('/api/v1/stats?api_token=valid-api-token-123&type=utm-campaigns&period=custom&start_date=2026-01-01&end_date=2026-01-07')
        ->assertOk()
        ->assertJsonStructure(['site', 'period', 'utm_campaigns']);
});
