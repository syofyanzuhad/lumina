<?php

use App\Enums\DeviceType;

test('fromScreenWidth returns correct device types for boundaries', function () {
    expect(DeviceType::fromScreenWidth(767))->toBe(DeviceType::Mobile)
        ->and(DeviceType::fromScreenWidth(768))->toBe(DeviceType::Tablet)
        ->and(DeviceType::fromScreenWidth(1024))->toBe(DeviceType::Tablet)
        ->and(DeviceType::fromScreenWidth(1025))->toBe(DeviceType::Desktop)
        ->and(DeviceType::fromScreenWidth(null))->toBe(DeviceType::Unknown)
        ->and(DeviceType::fromScreenWidth(0))->toBe(DeviceType::Unknown)
        ->and(DeviceType::fromScreenWidth(-1))->toBe(DeviceType::Unknown);
});
