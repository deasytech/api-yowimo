<?php

namespace App\Filament\Resources\UserBadges\Tables;

use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class UserBadgesTable
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
                TextColumn::make('badge.name')
                    ->label('Badge')
                    ->searchable()
                    ->description(fn ($record) => $record->badge?->key?->value),
                TextColumn::make('earned_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('reference_type')
                    ->label('Reference')
                    ->state(fn ($record): ?string => $record->reference_type
                        ? "{$record->reference_type} #{$record->reference_id}"
                        : null)
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('earned_at', 'desc')
            ->striped()
            ->filters([])
            ->recordActions([
                ViewAction::make(),
            ])
            ->toolbarActions([]);
    }
}
