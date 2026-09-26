<?php

namespace App\Filament\Resources\GameSessions\Schemas;

use App\Enums\GameSessionStatus;
use App\Filament\Support\BadgeColors;
use App\Filament\Support\InfolistSections;
use Filament\Infolists\Components\KeyValueEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class GameSessionInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Session')
                    ->icon(Heroicon::OutlinedPlay)
                    ->columns(2)
                    ->schema([
                        TextEntry::make('id'),
                        TextEntry::make('party.title')
                            ->label('Party'),
                        TextEntry::make('host.username')
                            ->label('Host'),
                        TextEntry::make('pack.name')
                            ->label('Pack'),
                        TextEntry::make('status')
                            ->badge()
                            ->color(fn (GameSessionStatus $state) => BadgeColors::gameSessionStatus($state)),
                        TextEntry::make('rounds_count')
                            ->label('Rounds'),
                        TextEntry::make('current_round_number')
                            ->label('Current round'),
                        TextEntry::make('started_at')
                            ->dateTime(),
                        TextEntry::make('ended_at')
                            ->dateTime()
                            ->placeholder('In progress'),
                    ]),

                Section::make('Live State')
                    ->icon(Heroicon::OutlinedArrowPath)
                    ->columns(2)
                    ->collapsible()
                    ->schema([
                        TextEntry::make('current_turn_index')
                            ->label('Current turn index'),
                        KeyValueEntry::make('turn_order')
                            ->label('Turn order')
                            ->columnSpanFull(),
                    ]),

                InfolistSections::timestamps(),
            ]);
    }
}
