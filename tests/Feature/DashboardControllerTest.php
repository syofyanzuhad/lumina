<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Lumina\Core\Models\Site;
use Tests\TestCase;

class DashboardControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_unauthenticated_user_is_redirected_to_login(): void
    {
        $response = $this->get('/dashboard');

        $response->assertRedirect('/login');
    }

    public function test_user_with_no_sites_is_redirected_to_site_create_page(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertRedirect('/sites/create');
    }

    public function test_authenticated_user_can_view_dashboard_with_sites_and_metrics(): void
    {
        $user = User::factory()->create();
        $site = Site::factory()->create([
            'owner_id' => $user->id,
            'domain' => 'my-site.com',
        ]);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard')
            ->has('sites', 1)
            ->where('activeSite.domain', 'my-site.com')
            ->where('period', '30d')
            ->has('overview.total_pageviews')
            ->has('overview.unique_visitors')
            ->has('overview.top_pages')
            ->has('overview.top_referrers')
            ->has('overview.daily_pageviews')
            ->has('overview.custom_events')
        );
    }

    public function test_user_can_switch_active_site_via_query_parameter(): void
    {
        $user = User::factory()->create();
        $site1 = Site::factory()->create(['owner_id' => $user->id, 'domain' => 'site-one.com']);
        $site2 = Site::factory()->create(['owner_id' => $user->id, 'domain' => 'site-two.com']);

        $response = $this->actingAs($user)->get("/dashboard?site_id={$site2->id}");

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard')
            ->where('activeSite.domain', 'site-two.com')
        );

        $this->assertEquals($site2->id, session('active_site_id'));
    }

    public function test_user_can_change_date_period_filter(): void
    {
        $user = User::factory()->create();
        $site = Site::factory()->create(['owner_id' => $user->id, 'domain' => 'my-site.com']);

        $response = $this->actingAs($user)->get('/dashboard?period=7d');

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard')
            ->where('period', '7d')
        );
    }

    public function test_user_cannot_access_dashboard_for_site_owned_by_another_user(): void
    {
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
    }
}
