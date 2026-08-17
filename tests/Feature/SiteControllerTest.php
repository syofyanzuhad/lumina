<?php

use App\Http\Controllers\SiteController;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;
use Lumina\Core\Enums\DeviceType;
use Lumina\Core\Models\Event;
use Lumina\Core\Models\Site;

test('it can create a site with normalized domain', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('sites.store'), [
        'domain' => 'https://www.example.com/path/to/page',
    ]);

    $response->assertRedirect();

    $this->assertDatabaseHas('sites', [
        'owner_id' => $user->id,
        'domain' => 'example.com',
    ]);
});

test('it requires a unique domain per user', function () {
    $user = User::factory()->create();
    Site::factory()->create(['owner_id' => $user->id, 'domain' => 'example.com']);

    $response = $this->actingAs($user)->post(route('sites.store'), [
        'domain' => 'example.com',
    ]);

    $response->assertInvalid(['domain']);
});

test('it lists the authenticated users sites', function () {
    $user = User::factory()->create();
    Site::factory()->create(['owner_id' => $user->id, 'domain' => 'mine.com']);
    Site::factory()->create(['owner_id' => User::factory()->create()->id, 'domain' => 'theirs.com']);

    $this->actingAs($user)->get(route('sites.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Sites/Index')
            ->has('sites', 1)
            ->where('sites.0.domain', 'mine.com')
        );
});

test('it shows the site creation form', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('sites.create'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('Sites/Create'));
});

test('it shows a site owned by the user and provisions an api token', function () {
    $user = User::factory()->create();
    $site = Site::factory()->create(['owner_id' => $user->id, 'domain' => 'mine.com', 'api_token' => null]);

    $this->actingAs($user)->get(route('sites.show', $site))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Sites/Show')
            ->where('site.domain', 'mine.com')
        );

    expect($site->refresh()->api_token)->not->toBeNull();
});

test('it forbids viewing a site owned by another user', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $site = Site::factory()->create(['owner_id' => $owner->id]);

    $this->actingAs($other)->get(route('sites.show', $site))->assertForbidden();
});

test('it deletes a site owned by the user', function () {
    $user = User::factory()->create();
    $site = Site::factory()->create(['owner_id' => $user->id]);

    $this->actingAs($user)->delete(route('sites.destroy', $site))
        ->assertRedirect(route('sites.index'));

    $this->assertDatabaseMissing('sites', ['id' => $site->id]);
});

test('it forbids deleting a site owned by another user', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $site = Site::factory()->create(['owner_id' => $owner->id]);

    $this->actingAs($other)->delete(route('sites.destroy', $site))->assertForbidden();

    $this->assertDatabaseHas('sites', ['id' => $site->id]);
});

test('it streams a csv export of site events', function () {
    $user = User::factory()->create();
    $site = Site::factory()->create(['owner_id' => $user->id, 'domain' => 'mine.com']);
    Event::factory()->create([
        'site_id' => $site->id,
        'path' => '/pricing',
        'referrer' => 'https://google.com',
        'device_type' => DeviceType::Desktop,
        // Pin to now so the event always falls inside the export's default
        // 29-day window (the factory default is a random date up to 30 days
        // back, which can land outside the window and make this flaky).
        'created_at' => now(),
    ]);

    $this->actingAs($user);

    $response = (new SiteController)->export($site);

    expect($response->getStatusCode())->toBe(200);

    ob_start();
    $response->sendContent();
    $content = ob_get_clean();

    expect($content)->toContain('ID,Path,Referrer')
        ->and($content)->toContain('Device Type')
        ->and($content)->toContain('/pricing');
});
