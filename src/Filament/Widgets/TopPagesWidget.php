<?php

namespace Lumina\Core\Filament\Widgets;

use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Lumina\Core\Models\Site;
use Lumina\Core\Services\AnalyticsService;

class TopPagesWidget extends BaseWidget
{
    public ?Site $site = null;

    public string $period = '30d';

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->records(fn () => $this->getRecords())
            ->columns([
                Tables\Columns\TextColumn::make('path')
                    ->label('Page Path')
                    ->searchable(),

                Tables\Columns\TextColumn::make('count')
                    ->label('Pageviews')
                    ->numeric()
                    ->sortable(),

                Tables\Columns\TextColumn::make('percentage')
                    ->label('Share')
                    ->formatStateUsing(fn ($state) => "{$state}%"),
            ]);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function getRecords(): array
    {
        if (! $this->site) {
            $this->site = Site::first();
        }

        if (! $this->site) {
            return [];
        }

        $start = now()->subDays($this->period === '7d' ? 6 : 29)->startOfDay();

        return app(AnalyticsService::class)
            ->getTopPages($this->site, $start, now()->endOfDay())
            ->toArray();
    }
}
