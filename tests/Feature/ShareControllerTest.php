<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia as Assert;
use Lumina\Core\Models\Site;
use Tests\TestCase;

class ShareControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_shared_dashboard_accessible_via_valid_token(): void
    {
        $user = User::factory()->create();
        $site = Site::factory()->create([
            'owner_id' => $user->id,
            'domain' => 'shared-site.com',
            'is_public' => true,
            'share_token' => 'valid-share-token-1234567890123',
        ]);

        $response = $this->get('/share/valid-share-token-1234567890123');

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Share/Show')
            ->where('requiresPassword', false)
            ->where('site.domain', 'shared-site.com')
            ->has('overview.total_pageviews')
            ->has('overview.unique_visitors')
        );
    }

    public function test_non_public_site_returns_404(): void
    {
        $user = User::factory()->create();
        Site::factory()->create([
            'owner_id' => $user->id,
            'domain' => 'private-site.com',
            'is_public' => false,
            'share_token' => 'private-token-123',
        ]);

        $response = $this->get('/share/private-token-123');

        $response->assertStatus(404);
    }

    public function test_invalid_token_returns_404(): void
    {
        $response = $this->get('/share/non-existent-token-xyz');

        $response->assertStatus(404);
    }

    public function test_password_protected_shared_site_requires_password(): void
    {
        $user = User::factory()->create();
        $site = Site::factory()->create([
            'owner_id' => $user->id,
            'domain' => 'protected-site.com',
            'is_public' => true,
            'share_token' => 'protected-token-123',
            'share_password' => Hash::make('secret123'),
        ]);

        $response = $this->get('/share/protected-token-123');

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Share/Show')
            ->where('requiresPassword', true)
            ->where('site.domain', 'protected-site.com')
        );
    }

    public function test_password_authentication_with_correct_password(): void
    {
        $user = User::factory()->create();
        $site = Site::factory()->create([
            'owner_id' => $user->id,
            'domain' => 'protected-site.com',
            'is_public' => true,
            'share_token' => 'protected-token-123',
            'share_password' => Hash::make('secret123'),
        ]);

        $response = $this->post('/share/protected-token-123/password', [
            'password' => 'secret123',
        ]);

        $response->assertRedirect('/share/protected-token-123');
        $this->assertTrue(session("share_auth_{$site->id}"));

        $followResponse = $this->get('/share/protected-token-123');
        $followResponse->assertStatus(200);
        $followResponse->assertInertia(fn (Assert $page) => $page
            ->component('Share/Show')
            ->where('requiresPassword', false)
            ->where('site.domain', 'protected-site.com')
        );
    }

    public function test_password_authentication_with_incorrect_password(): void
    {
        $user = User::factory()->create();
        $site = Site::factory()->create([
            'owner_id' => $user->id,
            'domain' => 'protected-site.com',
            'is_public' => true,
            'share_token' => 'protected-token-123',
            'share_password' => Hash::make('secret123'),
        ]);

        $response = $this->post('/share/protected-token-123/password', [
            'password' => 'wrongpassword',
        ]);

        $response->assertSessionHasErrors('password');
        $this->assertFalse(session()->has("share_auth_{$site->id}"));
    }

    public function test_unauthenticated_user_cannot_manage_site_sharing(): void
    {
        $user = User::factory()->create();
        $site = Site::factory()->create(['owner_id' => $user->id]);

        $updateResponse = $this->put("/sites/{$site->id}/share", [
            'is_public' => true,
        ]);
        $updateResponse->assertRedirect('/login');

        $regenResponse = $this->post("/sites/{$site->id}/share/regenerate");
        $regenResponse->assertRedirect('/login');
    }

    public function test_unauthorized_user_cannot_manage_site_sharing_of_another_user(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $site = Site::factory()->create(['owner_id' => $owner->id]);

        $updateResponse = $this->actingAs($otherUser)->put("/sites/{$site->id}/share", [
            'is_public' => true,
        ]);
        $updateResponse->assertStatus(403);

        $regenResponse = $this->actingAs($otherUser)->post("/sites/{$site->id}/share/regenerate");
        $regenResponse->assertStatus(403);
    }

    public function test_authorized_user_can_enable_public_sharing(): void
    {
        $user = User::factory()->create();
        $site = Site::factory()->create([
            'owner_id' => $user->id,
            'is_public' => false,
            'share_token' => null,
        ]);

        $response = $this->actingAs($user)->put("/sites/{$site->id}/share", [
            'is_public' => true,
        ]);

        $response->assertRedirect();
        $site->refresh();
        $this->assertTrue($site->is_public);
        $this->assertNotNull($site->share_token);
        $this->assertEquals(32, strlen($site->share_token));
    }

    public function test_authorized_user_can_set_and_clear_share_password(): void
    {
        $user = User::factory()->create();
        $site = Site::factory()->create([
            'owner_id' => $user->id,
            'is_public' => true,
            'share_token' => 'token-123',
            'share_password' => null,
        ]);

        // Set password
        $setResponse = $this->actingAs($user)->put("/sites/{$site->id}/share", [
            'is_public' => true,
            'share_password' => 'newpassword123',
        ]);

        $setResponse->assertRedirect();
        $site->refresh();
        $this->assertTrue($site->hasSharePassword());
        $this->assertTrue(Hash::check('newpassword123', $site->share_password));

        // Clear password
        $clearResponse = $this->actingAs($user)->put("/sites/{$site->id}/share", [
            'is_public' => true,
            'clear_password' => true,
        ]);

        $clearResponse->assertRedirect();
        $site->refresh();
        $this->assertFalse($site->hasSharePassword());
        $this->assertNull($site->share_password);
    }

    public function test_authorized_user_can_regenerate_share_token(): void
    {
        $user = User::factory()->create();
        $site = Site::factory()->create([
            'owner_id' => $user->id,
            'is_public' => true,
            'share_token' => 'old-token-123456789012345678901234',
        ]);

        $oldToken = $site->share_token;

        $response = $this->actingAs($user)->post("/sites/{$site->id}/share/regenerate");

        $response->assertRedirect();
        $site->refresh();
        $this->assertNotEquals($oldToken, $site->share_token);
        $this->assertEquals(32, strlen($site->share_token));
    }
}
