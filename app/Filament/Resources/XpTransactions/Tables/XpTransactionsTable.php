<?php

namespace App\Filament\Resources\XpTransactions\Tables;

use App\Enums\XpTransactionType;
use App\Filament\Support\BadgeColors;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class XpTransactionsTable
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
                TextColumn::make('type')
                    ->badge()
                    ->color(fn (XpTransactionType $state) => BadgeColors::xpTransactionType($state)),
                TextColumn::make('amount')
                    ->numeric()
                    ->suffix(' XP')
                    ->sortable(),
                TextColumn::make('game_session_id')
                    ->label('Session')
                    ->placeholder('—'),
                TextColumn::make('idempotency_key')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->striped()
            ->filters([
                SelectFilter::make('type')
                    ->options([
                        'turn_winner_vote' => 'Turn winner vote',
                        'turn_funny_vote' => 'Turn funny vote',
                        'turn_creativity_vote' => 'Turn creativity vote',
                        'challenge_completed' => 'Challenge completed',
                        'mvp_bonus' => 'MVP bonus',
                    ]),
            ])
            ->recordActions([
                ViewAction::make(),
            ])
            ->toolbarActions([]);
    }
}
