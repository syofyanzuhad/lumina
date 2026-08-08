<?php

namespace Lumina\Core\Filament\Resources;

use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Lumina\Core\Filament\Resources\SiteResource\Pages;
use Lumina\Core\Models\Site;

class SiteResource extends Resource
{
    protected static ?string $model = Site::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-globe-alt';

    protected static string|\UnitEnum|null $navigationGroup = 'Analytics';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Forms\Components\TextInput::make('domain')
                    ->required()
                    ->maxLength(255)
                    ->placeholder('example.com'),

                Forms\Components\Toggle::make('is_public')
                    ->label('Public Dashboard')
                    ->helperText('Allow anyone with the public share link to view analytics'),

                Forms\Components\TextInput::make('share_password')
                    ->password()
                    ->nullable()
                    ->helperText('Optional password for public share link access'),

                Forms\Components\TextInput::make('api_token')
                    ->label('API Token')
                    ->readOnly()
                    ->columnSpanFull()
                    ->helperText('Use this token for programmatic access via /api/v1/stats'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('domain')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_public')
                    ->boolean()
                    ->label('Public'),

                Tables\Columns\TextColumn::make('events_count')
                    ->counts('events')
                    ->label('Total Events')
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('generateToken')
                    ->label('Regenerate API Token')
                    ->icon('heroicon-o-key')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->action(function (Site $record) {
                        $record->update([
                            'api_token' => $record->generateApiToken(),
                        ]);

                        Notification::make()
                            ->title('API Token Generated')
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSites::route('/'),
            'create' => Pages\CreateSite::route('/create'),
            'edit' => Pages\EditSite::route('/{record}/edit'),
        ];
    }
}
