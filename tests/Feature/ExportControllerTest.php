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
        // Pin to now so the event always falls inside the export's default
        // 29-day window (the factory default is a random date up to 30 days
        // back, which can land outside the window and make this flaky).
        'created_at' => now(),
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

test('streamed CSV exports carry attachment and no-cache headers', function () {
    $user = User::factory()->create();
    $site = Site::factory()->create(['owner_id' => $user->id]);

    $response = $this->actingAs($user)
        ->get(route('sites.export', ['site' => $site, 'type' => 'pageviews', 'format' => 'csv']));

    $response->assertOk();

    expect($response->headers->get('content-disposition'))
        ->toBe('attachment; filename="'.$site->domain.'-pageviews-export.csv"')
        ->and($response->headers->get('pragma'))->toBe('no-cache')
        ->and($response->headers->get('cache-control'))->toContain('must-revalidate')
        ->and($response->headers->get('expires'))->toBe('0');
});

test('streamed JSON exports carry attachment and no-cache headers', function () {
    $user = User::factory()->create();
    $site = Site::factory()->create(['owner_id' => $user->id]);

    $response = $this->actingAs($user)
        ->get(route('sites.export', ['site' => $site, 'type' => 'events', 'format' => 'json']));

    $response->assertOk();

    expect($response->headers->get('content-disposition'))
        ->toBe('attachment; filename="'.$site->domain.'-events-export.json"')
        ->and($response->headers->get('pragma'))->toBe('no-cache')
        ->and($response->headers->get('expires'))->toBe('0');
});

test('summary exports include a content-disposition filename', function () {
    $user = User::factory()->create();
    $site = Site::factory()->create(['owner_id' => $user->id]);

    $response = $this->actingAs($user)
        ->get(route('sites.export', ['site' => $site, 'type' => 'summary', 'format' => 'json']));

    $response->assertOk();

    expect($response->headers->get('content-disposition'))
        ->toBe('attachment; filename="'.$site->domain.'-summary-export.json"');
});

test('user can stream JSON pageviews export', function () {
    $user = User::factory()->create();
    $site = Site::factory()->create(['owner_id' => $user->id]);
    Event::factory()->create([
        'site_id' => $site->id,
        'path' => '/json-test',
        'metadata' => null,
        'created_at' => now(),
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
        'created_at' => now(),
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

test('summary csv export includes breakdown sections when data exists', function () {
    $user = User::factory()->create();
    $site = Site::factory()->create(['owner_id' => $user->id, 'domain' => 'summary.com']);
    Event::factory()->count(3)->create([
        'site_id' => $site->id,
        'path' => '/blog/post',
        'created_at' => now(),
    ]);

    $response = $this->actingAs($user)
        ->get(route('sites.export', ['site' => $site, 'type' => 'summary', 'format' => 'csv']));

    $response->assertOk();

    ob_start();
    $response->sendContent();
    $csvContent = ob_get_clean();

    expect($csvContent)->toContain('Top Pages - Path')
        ->and($csvContent)->toContain('/blog/post')
        ->and($csvContent)->toContain('Top Referrers - Referrer')
        ->and($csvContent)->toContain('Device Types - Device')
        ->and($csvContent)->toContain('Top Browsers - Browser')
        ->and($csvContent)->toContain('Top Operating Systems - OS')
        ->and($csvContent)->toContain('Top Countries - Code / Name');
});

test('export supports 7d and custom date ranges', function () {
    $user = User::factory()->create();
    $site = Site::factory()->create(['owner_id' => $user->id]);

    $this->actingAs($user)
        ->get(route('sites.export', ['site' => $site, 'type' => 'pageviews', 'format' => 'csv', 'period' => '7d']))
        ->assertOk();

    $this->actingAs($user)
        ->get(route('sites.export', [
            'site' => $site,
            'type' => 'pageviews',
            'format' => 'csv',
            'period' => 'custom',
            'start_date' => now()->subDays(5)->toDateString(),
            'end_date' => now()->toDateString(),
        ]))
        ->assertOk();
});
