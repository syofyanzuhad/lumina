<?php

use Lumina\Core\Support\CountryHelper;

test('country helper resolves known ISO alpha-2 codes to English country names', function () {
    expect(CountryHelper::getName('US'))->toBe('United States')
        ->and(CountryHelper::getName('ID'))->toBe('Indonesia')
        ->and(CountryHelper::getName('jp'))->toBe('Japan');
});

test('country helper returns code or null when unknown or empty', function () {
    expect(CountryHelper::getName(null))->toBeNull()
        ->and(CountryHelper::getName(''))->toBeNull()
        ->and(CountryHelper::getName('XX'))->toBe('XX');
});
