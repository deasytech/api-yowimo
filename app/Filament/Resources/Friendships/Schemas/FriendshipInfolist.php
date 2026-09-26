<?php

namespace App\Filament\Resources\Friendships\Schemas;

use App\Enums\FriendshipStatus;
use App\Filament\Support\BadgeColors;
use App\Filament\Support\InfolistSections;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class FriendshipInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Friendship')
                    ->icon(Heroicon::OutlinedHeart)
                    ->columns(2)
                    ->schema([
                        TextEntry::make('id'),
                        TextEntry::make('sender.username')
                            ->label('Sender'),
                        TextEntry::make('receiver.username')
                            ->label('Receiver'),
                        TextEntry::make('status')
                            ->badge()
                            ->color(fn (FriendshipStatus $state) => BadgeColors::friendshipStatus($state)),
                        TextEntry::make('accepted_at')
                            ->dateTime()
                            ->placeholder('Never accepted'),
                    ]),

                InfolistSections::timestamps(),
            ]);
    }
}
