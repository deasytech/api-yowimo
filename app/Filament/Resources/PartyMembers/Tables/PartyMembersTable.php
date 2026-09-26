<?php

namespace App\Filament\Resources\PartyMembers\Tables;

use App\Enums\PartyMemberStatus;
use App\Filament\Support\BadgeColors;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class PartyMembersTable
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
                TextColumn::make('user.username')
                    ->label('Member')
                    ->searchable(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (PartyMemberStatus $state) => BadgeColors::partyMemberStatus($state)),
                TextColumn::make('joined_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('left_at')
                    ->dateTime()
                    ->placeholder('—'),
            ])
            ->defaultSort('joined_at', 'desc')
            ->striped()
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'active' => 'Active',
                        'left' => 'Left',
                        'removed' => 'Removed',
                    ]),
            ])
            ->recordActions([
                ViewAction::make(),
            ])
            ->toolbarActions([]);
    }
}
