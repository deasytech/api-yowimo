<?php

namespace App\Filament\Resources\Parties\Schemas;

use App\Enums\PartyMode;
use App\Enums\PartyStatus;
use App\Enums\PartyVisibility;
use App\Filament\Support\BadgeColors;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class PartyInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Overview')
                    ->icon(Heroicon::OutlinedIdentification)
                    ->columns(2)
                    ->schema([
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
                        TextEntry::make('room_code')
                            ->copyable(),
                    ]),

                Section::make('Status & Settings')
                    ->icon(Heroicon::OutlinedAdjustmentsHorizontal)
                    ->columns(3)
                    ->schema([
                        TextEntry::make('mode')
                            ->badge()
                            ->color(fn (PartyMode $state) => BadgeColors::partyMode($state)),
                        TextEntry::make('visibility')
                            ->badge()
                            ->color(fn (PartyVisibility $state) => BadgeColors::partyVisibility($state)),
                        TextEntry::make('status')
                            ->badge()
                            ->color(fn (PartyStatus $state) => BadgeColors::partyStatus($state)),
                        TextEntry::make('max_players')
                            ->numeric(),
                        TextEntry::make('players_count')
                            ->numeric(),
                        TextEntry::make('likes_count')
                            ->numeric(),
                        TextEntry::make('starts_at')
                            ->dateTime(),
                    ]),

                Section::make('Sponsorship')
                    ->icon(Heroicon::OutlinedGift)
                    ->columns(2)
                    ->collapsible()
                    ->schema([
                        TextEntry::make('is_sponsored')
                            ->label('Sponsored'),
                        TextEntry::make('sponsor_name'),
                    ]),

                Section::make('Timestamps')
                    ->icon(Heroicon::OutlinedClock)
                    ->columns(2)
                    ->collapsible()
                    ->schema([
                        TextEntry::make('created_at')
                            ->dateTime(),
                        TextEntry::make('updated_at')
                            ->dateTime(),
                    ]),
            ]);
    }
}
