<?php

use Lumina\Core\Support\ReferrerHelper;

test('referrer helper parses direct and empty values', function () {
    expect(ReferrerHelper::parseName(null))->toBe('Direct / None')
        ->and(ReferrerHelper::parseName(''))->toBe('Direct / None')
        ->and(ReferrerHelper::parseName('Direct'))->toBe('Direct / None')
        ->and(ReferrerHelper::parseName('direct'))->toBe('Direct / None');
});

test('referrer helper parses known platforms and www prefixes', function () {
    expect(ReferrerHelper::parseName('https://www.google.com/search?q=lumina'))->toBe('Google')
        ->and(ReferrerHelper::parseName('https://t.co/xyz123'))->toBe('X (Twitter)')
        ->and(ReferrerHelper::parseName('https://www.github.com/syofyanzuhad/lumina'))->toBe('GitHub');
});

test('referrer helper parses subdomains and unknown hosts', function () {
    expect(ReferrerHelper::parseName('https://m.facebook.com/posts/123'))->toBe('Facebook')
        ->and(ReferrerHelper::parseName('https://blog.mycustomsite.com/article'))->toBe('blog.mycustomsite.com')
        ->and(ReferrerHelper::parseName('plain-domain.com'))->toBe('plain-domain.com');
});
