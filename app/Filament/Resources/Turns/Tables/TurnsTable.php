<?php

namespace App\Filament\Resources\Turns\Tables;

use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class TurnsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->sortable(),
                TextColumn::make('game_session_id')
                    ->label('Session')
                    ->sortable(),
                TextColumn::make('round_id')
                    ->label('Round')
                    ->sortable(),
                TextColumn::make('user.username')
                    ->label('Player')
                    ->searchable(),
                TextColumn::make('pack_card_id')
                    ->label('Card')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('position')
                    ->label('Position')
                    ->sortable(),
                IconColumn::make('is_afk')
                    ->boolean(),
                TextColumn::make('started_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('completed_at')
                    ->dateTime()
                    ->placeholder('In progress'),
            ])
            ->defaultSort('id', 'desc')
            ->striped()
            ->filters([
                TernaryFilter::make('is_afk'),
            ])
            ->recordActions([
                ViewAction::make(),
            ])
            ->toolbarActions([]);
    }
}
