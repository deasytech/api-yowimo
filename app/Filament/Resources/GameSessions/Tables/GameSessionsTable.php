<?php

namespace App\Filament\Resources\GameSessions\Tables;

use App\Enums\GameSessionStatus;
use App\Filament\Support\BadgeColors;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class GameSessionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->sortable(),
                TextColumn::make('party.title')
                    ->label('Party')
                    ->searchable(),
                TextColumn::make('host.username')
                    ->label('Host')
                    ->searchable(),
                TextColumn::make('pack.name')
                    ->label('Pack'),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (GameSessionStatus $state) => BadgeColors::gameSessionStatus($state)),
                TextColumn::make('rounds_count')
                    ->label('Rounds')
                    ->numeric(),
                TextColumn::make('current_round_number')
                    ->label('Current round')
                    ->numeric(),
                TextColumn::make('started_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('ended_at')
                    ->dateTime()
                    ->placeholder('In progress'),
            ])
            ->defaultSort('started_at', 'desc')
            ->striped()
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'running' => 'Running',
                        'completed' => 'Completed',
                    ]),
            ])
            ->recordActions([
                ViewAction::make(),
            ])
            ->toolbarActions([]);
    }
}
