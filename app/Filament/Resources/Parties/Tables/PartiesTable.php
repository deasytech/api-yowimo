<?php

namespace App\Filament\Resources\Parties\Tables;

use App\Enums\PartyStatus;
use App\Enums\PartyVisibility;
use App\Filament\Support\BadgeColors;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class PartiesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->sortable(),
                TextColumn::make('title')
                    ->searchable()
                    ->weight(FontWeight::SemiBold),
                TextColumn::make('host.username')
                    ->label('Host')
                    ->searchable(),
                TextColumn::make('room_code')
                    ->searchable()
                    ->copyable(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (PartyStatus $state) => BadgeColors::partyStatus($state)),
                TextColumn::make('visibility')
                    ->badge()
                    ->color(fn (PartyVisibility $state) => BadgeColors::partyVisibility($state)),
                TextColumn::make('players_count')
                    ->numeric(),
                TextColumn::make('starts_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->striped()
            ->filters([
                TrashedFilter::make(),
                SelectFilter::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'scheduled' => 'Scheduled',
                        'live' => 'Live',
                        'ended' => 'Ended',
                        'cancelled' => 'Cancelled',
                    ]),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([]);
    }
}
