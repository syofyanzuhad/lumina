<?php

namespace Lumina\Core\Filament;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Lumina\Core\Filament\Resources\SiteResource;
use Lumina\Core\Filament\Widgets\LuminaOverviewWidget;
use Lumina\Core\Filament\Widgets\TopPagesWidget;

class LuminaPlugin implements Plugin
{
    public function getId(): string
    {
        return 'lumina-core';
    }

    public static function make(): static
    {
        return app(static::class);
    }

    public function register(Panel $panel): void
    {
        $panel
            ->resources([
                SiteResource::class,
            ])
            ->widgets([
                LuminaOverviewWidget::class,
                TopPagesWidget::class,
            ]);
    }

    public function boot(Panel $panel): void
    {
        //
    }
}
