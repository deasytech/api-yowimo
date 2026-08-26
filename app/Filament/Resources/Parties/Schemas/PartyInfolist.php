<?php

namespace App\Filament\Resources\Parties\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class PartyInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('id'),
                TextEntry::make('title'),
                TextEntry::make('description')
                    ->columnSpanFull(),
                TextEntry::make('host.username')
                    ->label('Host'),
                TextEntry::make('gameType.name')
                    ->label('Game type'),
                TextEntry::make('pack.name')
                    ->label('Pack'),
                TextEntry::make('room_code'),
                TextEntry::make('mode')
                    ->badge(),
                TextEntry::make('visibility')
                    ->badge(),
                TextEntry::make('status')
                    ->badge(),
                TextEntry::make('max_players'),
                TextEntry::make('players_count'),
                TextEntry::make('likes_count'),
                TextEntry::make('starts_at')
                    ->dateTime(),
                TextEntry::make('is_sponsored')
                    ->label('Sponsored'),
                TextEntry::make('sponsor_name'),
                TextEntry::make('created_at')
                    ->dateTime(),
                TextEntry::make('updated_at')
                    ->dateTime(),
            ]);
    }
}
