<?php

use App\Models\User;
use Lumina\Core\Models\Event;
use Lumina\Core\Models\Site;

test('export endpoint requires authentication', function () {
    $site = Site::factory()->create();

    $this->getJson(route('sites.export', $site))
        ->assertUnauthorized();
});

test('user cannot export data from site they do not own', function () {
    $owner = User::factory()->create();
    $otherUser = User::factory()->create();
    $site = Site::factory()->create(['owner_id' => $owner->id]);

    $this->actingAs($otherUser)
        ->get(route('sites.export', $site))
        ->assertForbidden();
});

test('user can stream CSV pageviews export', function () {
    $user = User::factory()->create();
    $site = Site::factory()->create(['owner_id' => $user->id]);
    Event::factory()->create([
        'site_id' => $site->id,
        'path' => '/blog/test-page',
        'metadata' => null,
    ]);

    $response = $this->actingAs($user)
        ->get(route('sites.export', ['site' => $site, 'type' => 'pageviews', 'format' => 'csv']));

    $response->assertOk();
    $response->assertHeader('content-type', 'text/csv; charset=UTF-8');

    ob_start();
    $response->sendContent();
    $content = ob_get_clean();

    expect($content)->toContain('ID,Path,Referrer')
        ->and($content)->toContain('/blog/test-page');
});

test('user can stream JSON pageviews export', function () {
    $user = User::factory()->create();
    $site = Site::factory()->create(['owner_id' => $user->id]);
    Event::factory()->create([
        'site_id' => $site->id,
        'path' => '/json-test',
        'metadata' => null,
    ]);

    $response = $this->actingAs($user)
        ->get(route('sites.export', ['site' => $site, 'type' => 'pageviews', 'format' => 'json']));

    $response->assertOk();
    $response->assertHeader('content-type', 'application/json');

    ob_start();
    $response->sendContent();
    $content = ob_get_clean();

    expect($content)->toContain('/json-test')
        ->and(json_decode($content, true))->toBeArray();
});

test('user can stream custom events export in CSV and JSON', function () {
    $user = User::factory()->create();
    $site = Site::factory()->create(['owner_id' => $user->id]);
    Event::factory()->create([
        'site_id' => $site->id,
        'path' => '/pricing',
        'metadata' => ['name' => 'Button Clicked', 'plan' => 'pro'],
    ]);

    $responseCsv = $this->actingAs($user)
        ->get(route('sites.export', ['site' => $site, 'type' => 'events', 'format' => 'csv']));

    $responseCsv->assertOk();

    ob_start();
    $responseCsv->sendContent();
    $csvContent = ob_get_clean();

    expect($csvContent)->toContain('Button Clicked');

    $responseJson = $this->actingAs($user)
        ->get(route('sites.export', ['site' => $site, 'type' => 'events', 'format' => 'json']));

    $responseJson->assertOk();

    ob_start();
    $responseJson->sendContent();
    $jsonContent = ob_get_clean();

    expect($jsonContent)->toContain('Button Clicked');
});

test('user can export summary metrics in JSON and CSV', function () {
    $user = User::factory()->create();
    $site = Site::factory()->create(['owner_id' => $user->id]);

    $responseJson = $this->actingAs($user)
        ->get(route('sites.export', ['site' => $site, 'type' => 'summary', 'format' => 'json']));

    $responseJson->assertOk();

    ob_start();
    $responseJson->sendContent();
    $jsonContent = ob_get_clean();

    expect(json_decode($jsonContent, true))->toHaveKey('total_pageviews');

    $responseCsv = $this->actingAs($user)
        ->get(route('sites.export', ['site' => $site, 'type' => 'summary', 'format' => 'csv']));

    $responseCsv->assertOk();

    ob_start();
    $responseCsv->sendContent();
    $csvContent = ob_get_clean();

    expect($csvContent)->toContain('Total Pageviews');
});
