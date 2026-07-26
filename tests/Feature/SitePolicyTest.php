<?php

use App\Models\Site;
use App\Models\User;

test('users can view, update, and delete their own sites', function () {
    $user = User::factory()->create();
    $site = Site::factory()->create(['owner_id' => $user->id]);

    expect($user->can('view', $site))->toBeTrue()
        ->and($user->can('update', $site))->toBeTrue()
        ->and($user->can('delete', $site))->toBeTrue();
});

test('users cannot view, update, or delete other users sites', function () {
    $owner = User::factory()->create();
    $site = Site::factory()->create(['owner_id' => $owner->id]);

    $otherUser = User::factory()->create();

    expect($otherUser->can('view', $site))->toBeFalse()
        ->and($otherUser->can('update', $site))->toBeFalse()
        ->and($otherUser->can('delete', $site))->toBeFalse();
});
