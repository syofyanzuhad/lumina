<?php

use App\Enums\DeviceType;
use App\Models\Event;
use App\Models\Site;

test('it can create an event using the factory', function () {
    $event = Event::factory()->create();

    expect($event)->toBeInstanceOf(Event::class)
        ->and($event->id)->not->toBeNull();
});

test('it belongs to a site', function () {
    $event = Event::factory()->create();

    expect($event->site)->toBeInstanceOf(Site::class);
});

test('it casts device_type to the DeviceType enum', function () {
    $event = Event::factory()->desktop()->create();

    expect($event->device_type)->toBeInstanceOf(DeviceType::class)
        ->and($event->device_type)->toBe(DeviceType::Desktop);
});

test('it does not update the updated_at column', function () {
    $event = Event::factory()->create();

    expect(array_key_exists('updated_at', $event->getAttributes()))->toBeFalse();

    $event->update(['path' => '/new-path']);

    expect($event->fresh()->path)->toBe('/new-path')
        ->and(array_key_exists('updated_at', $event->fresh()->getAttributes()))->toBeFalse();
});
