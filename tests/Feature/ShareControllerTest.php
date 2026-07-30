<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Lumina\Core\Models\Site;

uses(RefreshDatabase::class);

test('public share link shows dashboard metrics when site is public and not password protected', function () {
    $site = Site::factory()->public()->create();

    $this->get(route('sites.share.show', $site->share_token))
        ->assertStatus(200)
        ->assertInertia(fn (Assert $page) => $page
            ->component('Share/Show')
            ->where('requiresPassword', false)
            ->where('site.domain', $site->domain)
            ->has('overview')
        );
});

test('public share link returns 404 when site is not public', function () {
    $site = Site::factory()->create(['is_public' => false, 'share_token' => 'some-token']);

    $this->get(route('sites.share.show', $site->share_token))
        ->assertStatus(404);
});

test('invalid share token returns 404', function () {
    $this->get(route('sites.share.show', 'invalid-token-123456789012345678'))
        ->assertStatus(404);
});

test('password protected share link requires password when unauthenticated', function () {
    $site = Site::factory()->passwordProtected('secret123')->create();

    $this->get(route('sites.share.show', $site->share_token))
        ->assertStatus(200)
        ->assertInertia(fn (Assert $page) => $page
            ->component('Share/Show')
            ->where('requiresPassword', true)
            ->where('site.domain', $site->domain)
            ->missing('overview')
        );
});

test('authenticating with incorrect password fails and redirects back with error', function () {
    $site = Site::factory()->passwordProtected('secret123')->create();

    $this->post(route('sites.share.authenticate', $site->share_token), [
        'password' => 'wrongpassword',
    ])
        ->assertSessionHasErrors('password');

    $this->get(route('sites.share.show', $site->share_token))
        ->assertStatus(200)
        ->assertInertia(fn (Assert $page) => $page
            ->component('Share/Show')
            ->where('requiresPassword', true)
        );
});

test('authenticating with correct password sets session flag and grants access', function () {
    $site = Site::factory()->passwordProtected('secret123')->create();

    $this->post(route('sites.share.authenticate', $site->share_token), [
        'password' => 'secret123',
    ])
        ->assertRedirect(route('sites.share.show', $site->share_token));

    $this->get(route('sites.share.show', $site->share_token))
        ->assertStatus(200)
        ->assertInertia(fn (Assert $page) => $page
            ->component('Share/Show')
            ->where('requiresPassword', false)
            ->has('overview')
        );
});

test('owner can update site share settings', function () {
    $user = User::factory()->create();
    $site = Site::factory()->create(['owner_id' => $user->id, 'is_public' => false]);

    $this->actingAs($user)
        ->put(route('sites.share.update', $site), [
            'is_public' => true,
            'share_password' => 'newpassword123',
        ])
        ->assertRedirect();

    $site->refresh();
    expect($site->is_public)->toBeTrue();
    expect($site->share_token)->not()->toBeNull();
    expect($site->hasSharePassword())->toBeTrue();
});

test('owner can clear share password', function () {
    $user = User::factory()->create();
    $site = Site::factory()->passwordProtected('secret123')->create(['owner_id' => $user->id]);

    $this->actingAs($user)
        ->put(route('sites.share.update', $site), [
            'is_public' => true,
            'clear_password' => true,
        ])
        ->assertRedirect();

    $site->refresh();
    expect($site->hasSharePassword())->toBeFalse();
});

test('non-owner cannot update site share settings', function () {
    $owner = User::factory()->create();
    $otherUser = User::factory()->create();
    $site = Site::factory()->create(['owner_id' => $owner->id]);

    $this->actingAs($otherUser)
        ->put(route('sites.share.update', $site), [
            'is_public' => true,
        ])
        ->assertStatus(403);
});

test('owner can regenerate site share token', function () {
    $user = User::factory()->create();
    $site = Site::factory()->public('old-token-12345678901234567890123')->create(['owner_id' => $user->id]);

    $oldToken = $site->share_token;

    $this->actingAs($user)
        ->post(route('sites.share.regenerate', $site))
        ->assertRedirect();

    $site->refresh();
    expect($site->share_token)->not()->toBe($oldToken);
    expect(strlen($site->share_token))->toBe(32);
});

test('non-owner cannot regenerate site share token', function () {
    $owner = User::factory()->create();
    $otherUser = User::factory()->create();
    $site = Site::factory()->public()->create(['owner_id' => $owner->id]);

    $this->actingAs($otherUser)
        ->post(route('sites.share.regenerate', $site))
        ->assertStatus(403);
});
