<?php

namespace App\Filament\Resources\WalletTransactions\Tables;

use App\Enums\WalletTransactionType;
use App\Filament\Support\BadgeColors;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class WalletTransactionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->sortable(),
                TextColumn::make('wallet.user.username')
                    ->label('User')
                    ->searchable(),
                TextColumn::make('type')
                    ->badge()
                    ->color(fn (WalletTransactionType $state) => BadgeColors::walletTransactionType($state)),
                TextColumn::make('amount')
                    ->numeric()
                    ->suffix(' tokens')
                    ->sortable(),
                TextColumn::make('balance_after')
                    ->numeric()
                    ->suffix(' tokens'),
                TextColumn::make('idempotency_key')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('description')
                    ->limit(50)
                    ->tooltip(fn ($state) => $state),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->striped()
            ->filters([
                SelectFilter::make('type')
                    ->options([
                        'top_up' => 'Top up',
                        'purchase' => 'Purchase',
                        'refund' => 'Refund',
                        'bonus' => 'Bonus',
                        'reward' => 'Reward',
                        'adjustment' => 'Adjustment',
                    ]),
            ])
            ->recordActions([
                ViewAction::make(),
            ])
            ->toolbarActions([]);
    }
}
