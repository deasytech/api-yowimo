<?php

namespace App\Filament\Resources\WebhookEvents\Tables;

use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class WebhookEventsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->sortable(),
                TextColumn::make('provider')
                    ->badge()
                    ->color('info'),
                TextColumn::make('event_type')
                    ->searchable(),
                TextColumn::make('event_id')
                    ->label('Event ID')
                    ->limit(20)
                    ->copyable()
                    ->tooltip(fn (string $state): string => $state),
                TextColumn::make('processed_at')
                    ->label('Processed')
                    ->dateTime()
                    ->placeholder('Pending'),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->striped()
            ->filters([
                SelectFilter::make('provider')
                    ->options([
                        'clerk' => 'Clerk',
                        'paystack' => 'Paystack',
                    ]),
            ])
            ->recordActions([
                ViewAction::make(),
            ])
            ->toolbarActions([]);
    }
}
