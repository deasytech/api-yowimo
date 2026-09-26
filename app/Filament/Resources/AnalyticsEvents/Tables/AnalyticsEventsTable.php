<?php

namespace App\Filament\Resources\AnalyticsEvents\Tables;

use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AnalyticsEventsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->sortable(),
                TextColumn::make('event')
                    ->badge()
                    ->color('info')
                    ->searchable(),
                TextColumn::make('user.username')
                    ->label('User')
                    ->searchable()
                    ->placeholder('Guest'),
                TextColumn::make('country')
                    ->placeholder('—'),
                TextColumn::make('device')
                    ->limit(20)
                    ->tooltip(fn (?string $state): ?string => $state)
                    ->placeholder('—'),
                TextColumn::make('ip')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->striped()
            ->filters([])
            ->recordActions([
                ViewAction::make(),
            ])
            ->toolbarActions([]);
    }
}
