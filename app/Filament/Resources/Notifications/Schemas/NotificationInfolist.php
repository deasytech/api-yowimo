<?php

namespace App\Filament\Resources\Notifications\Schemas;

use Filament\Infolists\Components\KeyValueEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class NotificationInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Notification')
                    ->icon(Heroicon::OutlinedBell)
                    ->columns(2)
                    ->schema([
                        TextEntry::make('id'),
                        TextEntry::make('user.username')
                            ->label('User'),
                        TextEntry::make('title'),
                        TextEntry::make('type'),
                        TextEntry::make('body')
                            ->columnSpanFull(),
                        TextEntry::make('read_at')
                            ->label('Read')
                            ->dateTime()
                            ->placeholder('Unread'),
                        KeyValueEntry::make('metadata')
                            ->label('Metadata')
                            ->columnSpanFull(),
                    ]),

                Section::make('Timestamps')
                    ->icon(Heroicon::OutlinedClock)
                    ->schema([
                        TextEntry::make('created_at')
                            ->dateTime(),
                    ]),
            ]);
    }
}
