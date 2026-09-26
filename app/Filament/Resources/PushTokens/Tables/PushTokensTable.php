<?php

namespace App\Filament\Resources\PushTokens\Tables;

use App\Enums\PushPlatform;
use App\Filament\Support\BadgeColors;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class PushTokensTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->sortable(),
                TextColumn::make('user.username')
                    ->label('User')
                    ->searchable(),
                TextColumn::make('platform')
                    ->badge()
                    ->color(fn (PushPlatform $state) => BadgeColors::pushPlatform($state)),
                TextColumn::make('token')
                    ->label('Token')
                    ->limit(30)
                    ->copyable()
                    ->tooltip(fn (string $state): string => $state),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->striped()
            ->filters([
                SelectFilter::make('platform')
                    ->options([
                        'ios' => 'iOS',
                        'android' => 'Android',
                    ]),
            ])
            ->recordActions([
                ViewAction::make(),
            ])
            ->toolbarActions([]);
    }
}
