<?php

use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;
use Lumina\Core\Models\Site;

test('unauthenticated user is redirected to login', function () {
    $response = $this->get('/dashboard');

    $response->assertRedirect('/login');
});

test('user with no sites is redirected to site create page', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/dashboard');

    $response->assertRedirect('/sites/create');
});

test('authenticated user can view dashboard with sites and metrics', function () {
    $user = User::factory()->create();
    $site = Site::factory()->create([
        'owner_id' => $user->id,
        'domain' => 'my-site.com',
    ]);

    $response = $this->actingAs($user)->get('/dashboard');

    $response->assertRedirect("/dashboard?site_id={$site->id}");
});

test('user can switch active site via query parameter', function () {
    $user = User::factory()->create();
    $site1 = Site::factory()->create(['owner_id' => $user->id, 'domain' => 'site-one.com']);
    $site2 = Site::factory()->create(['owner_id' => $user->id, 'domain' => 'site-two.com']);

    $response = $this->actingAs($user)->get("/dashboard?site_id={$site2->id}");

    $response->assertStatus(200);
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Dashboard')
        ->where('activeSite.domain', 'site-two.com')
    );
});

test('user can change date period filter', function () {
    $user = User::factory()->create();
    $site = Site::factory()->create(['owner_id' => $user->id, 'domain' => 'my-site.com']);

    $response = $this->actingAs($user)->get("/dashboard?site_id={$site->id}&period=7d");

    $response->assertStatus(200);
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Dashboard')
        ->where('period', '7d')
    );
});

test('user cannot access dashboard for site owned by another user', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();

    $user1Site = Site::factory()->create(['owner_id' => $user1->id, 'domain' => 'user1-site.com']);
    $user2Site = Site::factory()->create(['owner_id' => $user2->id, 'domain' => 'user2-site.com']);

    // User 1 requests User 2's site_id
    $response = $this->actingAs($user1)->get("/dashboard?site_id={$user2Site->id}");

    $response->assertStatus(200);
    // Falls back to User 1's own site
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Dashboard')
        ->where('activeSite.domain', 'user1-site.com')
    );
});

test('user can view custom events tab on inertia dashboard', function () {
    $user = User::factory()->create();
    $site = Site::factory()->create(['owner_id' => $user->id, 'domain' => 'my-site.com']);

    $response = $this->actingAs($user)->get("/dashboard?site_id={$site->id}&tab=events");

    $response->assertStatus(200);
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Dashboard')
        ->where('activeTab', 'events')
        ->has('custom_event_summary')
        ->has('custom_events_list')
        ->has('custom_event_timeline')
        ->has('custom_event_logs')
    );
});

test('user can filter custom events by event name', function () {
    $user = User::factory()->create();
    $site = Site::factory()->create(['owner_id' => $user->id, 'domain' => 'my-site.com']);

    $response = $this->actingAs($user)->get("/dashboard?site_id={$site->id}&tab=events&event=purchase_click");

    $response->assertStatus(200);
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Dashboard')
        ->where('activeTab', 'events')
        ->where('selectedEvent', 'purchase_click')
        ->has('custom_event_property_keys')
        ->has('custom_event_property_breakdown')
    );
});

test('inertia dashboard returns custom event timeline and property breakdowns', function () {
    $user = User::factory()->create();
    $site = Site::factory()->create(['owner_id' => $user->id, 'domain' => 'my-site.com']);

    $response = $this->actingAs($user)->get("/dashboard?site_id={$site->id}&tab=events&event=purchase_click&property=plan");

    $response->assertStatus(200);
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Dashboard')
        ->where('activeTab', 'events')
        ->where('selectedEvent', 'purchase_click')
        ->where('selectedPropertyKey', 'plan')
        ->has('custom_event_property_keys')
        ->has('custom_event_property_breakdown')
    );
});

test('dashboard supports today and custom periods', function () {
    $user = User::factory()->create();
    $site = Site::factory()->create(['owner_id' => $user->id, 'domain' => 'my-site.com']);

    $this->actingAs($user)->get("/dashboard?site_id={$site->id}&period=today")
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('period', 'today'));

    $this->actingAs($user)->get("/dashboard?site_id={$site->id}&period=custom&start_date=".now()->subDays(3)->toDateString().'&end_date='.now()->toDateString())
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('period', 'custom'));
});

test('dashboard applies filters to metrics', function () {
    $user = User::factory()->create();
    $site = Site::factory()->create(['owner_id' => $user->id, 'domain' => 'my-site.com']);

    $this->actingAs($user)->get("/dashboard?site_id={$site->id}&device=desktop")
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('filters.device', 'desktop'));
});

test('breakdown endpoint supports all breakdown types and filters', function () {
    $user = User::factory()->create();
    $site = Site::factory()->create(['owner_id' => $user->id]);

    foreach (['referrers', 'browsers', 'os', 'locations', 'utm', 'devices'] as $type) {
        $this->actingAs($user)->getJson(route('dashboard.breakdown', [
            'site_id' => $site->id,
            'type' => $type,
            'device' => 'desktop',
        ]))
            ->assertOk()
            ->assertJson(['type' => $type]);
    }
});
