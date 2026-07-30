<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Lumina\Core\Models\Site;
use Tests\TestCase;

pest()->extend(TestCase::class);
uses(RefreshDatabase::class);

test('site model helper methods and factory public state', function () {
    $site = Site::factory()->public()->create();

    expect($site->is_public)->toBeTrue();
    expect($site->share_token)->not()->toBeNull();
    expect(strlen($site->share_token))->toBe(32);
    expect($site->isPubliclyAccessible())->toBeTrue();
    expect($site->hasSharePassword())->toBeFalse();
});

test('site model password protected factory state', function () {
    $site = Site::factory()->passwordProtected('my-password')->create();

    expect($site->is_public)->toBeTrue();
    expect($site->share_token)->not()->toBeNull();
    expect($site->hasSharePassword())->toBeTrue();
    expect(Hash::check('my-password', $site->share_password))->toBeTrue();
});

test('generateShareToken generates 32 char random string', function () {
    $site = new Site();
    $token = $site->generateShareToken();

    expect(strlen($token))->toBe(32);
});
