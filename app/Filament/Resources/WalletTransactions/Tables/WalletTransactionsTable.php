<?php

namespace App\Filament\Resources\WalletTransactions\Tables;

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
                    ->badge(),
                TextColumn::make('amount')
                    ->sortable(),
                TextColumn::make('balance_after'),
                TextColumn::make('idempotency_key')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('description')
                    ->limit(50),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('type')
                    ->options([
                        'top_up' => 'Top up',
                        'purchase' => 'Purchase',
                        'refund' => 'Refund',
                        'bonus' => 'Bonus',
                        'adjustment' => 'Adjustment',
                    ]),
            ])
            ->recordActions([
                ViewAction::make(),
            ])
            ->toolbarActions([]);
    }
}
