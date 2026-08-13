<?php

use App\Models\User;
use App\Policies\SitePolicy;
use Lumina\Core\Models\Site;

test('site policy viewAny and create allow all authenticated users', function () {
    $user = User::factory()->create();
    $policy = new SitePolicy;

    expect($policy->viewAny($user))->toBeTrue()
        ->and($policy->create($user))->toBeTrue();
});

test('site policy checks owner for view, update, delete, restore, forceDelete', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $site = Site::factory()->create(['owner_id' => $owner->id]);
    $policy = new SitePolicy;

    expect($policy->view($owner, $site))->toBeTrue()
        ->and($policy->view($other, $site))->toBeFalse()
        ->and($policy->update($owner, $site))->toBeTrue()
        ->and($policy->update($other, $site))->toBeFalse()
        ->and($policy->delete($owner, $site))->toBeTrue()
        ->and($policy->delete($other, $site))->toBeFalse()
        ->and($policy->restore($owner, $site))->toBeTrue()
        ->and($policy->restore($other, $site))->toBeFalse()
        ->and($policy->forceDelete($owner, $site))->toBeTrue()
        ->and($policy->forceDelete($other, $site))->toBeFalse();
});
