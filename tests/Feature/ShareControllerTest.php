<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia as Assert;
use Lumina\Core\Models\Site;

test('public shared dashboard accessible via valid token', function () {
    $user = User::factory()->create();
    $site = Site::factory()->create([
        'owner_id' => $user->id,
        'domain' => 'shared-site.com',
        'is_public' => true,
        'share_token' => 'valid-share-token-1234567890123',
    ]);

    $response = $this->get('/share/valid-share-token-1234567890123');

    $response->assertStatus(200);
    // KPI props are merged at the top level (same contract as the dashboard).
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Share/Show')
        ->where('requiresPassword', false)
        ->where('site.domain', 'shared-site.com')
        ->has('total_pageviews')
        ->has('unique_visitors')
    );
});

test('non public site returns 404', function () {
    $user = User::factory()->create();
    Site::factory()->create([
        'owner_id' => $user->id,
        'domain' => 'private-site.com',
        'is_public' => false,
        'share_token' => 'private-token-123',
    ]);

    $response = $this->get('/share/private-token-123');

    $response->assertStatus(404);
});

test('invalid token returns 404', function () {
    $response = $this->get('/share/non-existent-token-xyz');

    $response->assertStatus(404);
});

test('password protected shared site requires password', function () {
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
});

test('password authentication with correct password', function () {
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
});

test('password authentication with incorrect password', function () {
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
});

test('unauthenticated user cannot manage site sharing', function () {
    $user = User::factory()->create();
    $site = Site::factory()->create(['owner_id' => $user->id]);

    $updateResponse = $this->put("/sites/{$site->id}/share", [
        'is_public' => true,
    ]);
    $updateResponse->assertRedirect('/login');

    $regenResponse = $this->post("/sites/{$site->id}/share/regenerate");
    $regenResponse->assertRedirect('/login');
});

test('unauthorized user cannot manage site sharing of another user', function () {
    $owner = User::factory()->create();
    $otherUser = User::factory()->create();
    $site = Site::factory()->create(['owner_id' => $owner->id]);

    $updateResponse = $this->actingAs($otherUser)->put("/sites/{$site->id}/share", [
        'is_public' => true,
    ]);
    $updateResponse->assertStatus(403);

    $regenResponse = $this->actingAs($otherUser)->post("/sites/{$site->id}/share/regenerate");
    $regenResponse->assertStatus(403);
});

test('authorized user can enable public sharing', function () {
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
});

test('authorized user can set and clear share password', function () {
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
});

test('authorized user can regenerate share token', function () {
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
});

test('public share dashboard honors period and date range', function () {
    $site = Site::factory()->public('share-token-456')->create(['domain' => 'public.com']);

    $this->get('/share/share-token-456?period=7d')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('period', '7d'));

    $this->get('/share/share-token-456?period=custom&start_date='.now()->subDays(3)->toDateString().'&end_date='.now()->toDateString())
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('period', 'custom'));
});

test('share breakdown supports additional breakdown types', function () {
    $site = Site::factory()->public('share-token-789')->create(['domain' => 'public.com']);

    foreach (['referrers', 'browsers', 'os', 'locations', 'utm', 'devices'] as $type) {
        $this->getJson('/share/share-token-789/breakdown?type='.$type)
            ->assertOk()
            ->assertJson(['type' => $type]);
    }
});
