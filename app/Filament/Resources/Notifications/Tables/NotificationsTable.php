<?php

namespace App\Filament\Resources\Notifications\Tables;

use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class NotificationsTable
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
                TextColumn::make('title')
                    ->searchable()
                    ->limit(40)
                    ->tooltip(fn (string $state): string => $state),
                TextColumn::make('type')
                    ->badge()
                    ->color('info'),
                TextColumn::make('read_at')
                    ->label('Read')
                    ->dateTime()
                    ->placeholder('Unread'),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->striped()
            ->filters([
                TernaryFilter::make('read_at')
                    ->label('Read')
                    ->nullable(),
            ])
            ->recordActions([
                ViewAction::make(),
            ])
            ->toolbarActions([]);
    }
}
