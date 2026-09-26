<?php

namespace App\Filament\Resources\Friendships\Tables;

use App\Enums\FriendshipStatus;
use App\Filament\Support\BadgeColors;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class FriendshipsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->sortable(),
                TextColumn::make('sender.username')
                    ->label('Sender')
                    ->searchable(),
                TextColumn::make('receiver.username')
                    ->label('Receiver')
                    ->searchable(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (FriendshipStatus $state) => BadgeColors::friendshipStatus($state)),
                TextColumn::make('accepted_at')
                    ->dateTime()
                    ->placeholder('—'),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->striped()
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'accepted' => 'Accepted',
                        'rejected' => 'Rejected',
                        'cancelled' => 'Cancelled',
                        'removed' => 'Removed',
                    ]),
            ])
            ->recordActions([
                ViewAction::make(),
            ])
            ->toolbarActions([]);
    }
}
