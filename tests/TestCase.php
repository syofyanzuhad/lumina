<?php

namespace Tests;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Laravel\Fortify\Features;

abstract class TestCase extends BaseTestCase
{
    /**
     * Creates the application.
     *
     * Guard: a stale `bootstrap/cache/config.php` (produced by `php artisan
     * optimize` or `config:cache`) bakes in the dev `.env` values and makes
     * Laravel skip environment loading entirely — every test would then run
     * against the local dev database with CSRF protection active, regardless
     * of phpunit.xml or `.env.testing`. Delete it before the kernel
     * bootstraps so tests always boot from the testing environment.
     */
    public function createApplication(): Application
    {
        $cachedConfig = Application::inferBasePath().'/bootstrap/cache/config.php';

        if (is_file($cachedConfig)) {
            @unlink($cachedConfig);
        }

        $app = require Application::inferBasePath().'/bootstrap/app.php';

        $app->make(Kernel::class)->bootstrap();

        return $app;
    }

    protected function skipUnlessFortifyHas(string $feature, ?string $message = null): void
    {
        if (! Features::enabled($feature)) {
            $this->markTestSkipped($message ?? "Fortify feature [{$feature}] is not enabled.");
        }
    }
}
