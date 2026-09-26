<?php

namespace App\Filament\Resources\PaymentMethods\Tables;

use App\Models\PaymentMethod;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class PaymentMethodsTable
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
                TextColumn::make('provider')
                    ->badge()
                    ->color('info'),
                TextColumn::make('card_type')
                    ->placeholder('—'),
                TextColumn::make('last4')
                    ->label('Last 4')
                    ->placeholder('—'),
                TextColumn::make('exp_month')
                    ->label('Expires')
                    ->state(fn (PaymentMethod $record): ?string => ($record->exp_month && $record->exp_year) ? "{$record->exp_month}/{$record->exp_year}" : null)
                    ->placeholder('—'),
                TextColumn::make('bank')
                    ->placeholder('—'),
                IconColumn::make('is_default')
                    ->boolean(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->striped()
            ->filters([
                SelectFilter::make('provider')
                    ->options([
                        'paystack' => 'Paystack',
                    ]),
            ])
            ->recordActions([
                ViewAction::make(),
            ])
            ->toolbarActions([]);
    }
}
