<?php

namespace App\Filament\Resources\Votes\Tables;

use App\Enums\VoteCategory;
use App\Filament\Support\BadgeColors;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class VotesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->sortable(),
                TextColumn::make('turn_id')
                    ->label('Turn')
                    ->sortable(),
                TextColumn::make('voter.username')
                    ->label('Voter')
                    ->searchable(),
                TextColumn::make('category')
                    ->badge()
                    ->color(fn (VoteCategory $state) => BadgeColors::voteCategory($state)),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->striped()
            ->filters([
                SelectFilter::make('category')
                    ->options([
                        'winner' => 'Winner',
                        'funny' => 'Funny',
                        'creativity' => 'Creativity',
                    ]),
            ])
            ->recordActions([
                ViewAction::make(),
            ])
            ->toolbarActions([]);
    }
}
