<?php

use Lumina\Core\Models\Event;
use Lumina\Core\Models\Site;
use App\Models\User;

test('it can create a site using the factory', function () {
    $site = Site::factory()->create();

    expect($site)->toBeInstanceOf(Site::class)
        ->and($site->id)->not->toBeNull();
});

test('it belongs to a user', function () {
    $site = Site::factory()->create();

    expect($site->owner)->toBeInstanceOf(User::class);
});

test('it converts the domain to lowercase on save', function () {
    $site = Site::factory()->create([
        'domain' => 'ExAmPlE.cOm',
    ]);

    expect($site->domain)->toBe('example.com');
    $this->assertDatabaseHas('sites', [
        'domain' => 'example.com',
    ]);
});

test('it deletes events when site is deleted', function () {
    $site = Site::factory()->has(Event::factory()->count(3))->create();

    expect($site->events()->count())->toBe(3);

    $site->delete();

    $this->assertDatabaseEmpty('events');
});
