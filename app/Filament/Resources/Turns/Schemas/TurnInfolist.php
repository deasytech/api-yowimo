<?php

namespace App\Filament\Resources\Turns\Schemas;

use App\Filament\Support\InfolistSections;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class TurnInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Turn')
                    ->icon(Heroicon::OutlinedForward)
                    ->columns(2)
                    ->schema([
                        TextEntry::make('id'),
                        TextEntry::make('game_session_id')
                            ->label('Session'),
                        TextEntry::make('round_id')
                            ->label('Round'),
                        TextEntry::make('user.username')
                            ->label('Player'),
                        TextEntry::make('pack_card_id')
                            ->label('Card')
                            ->placeholder('No card drawn'),
                        TextEntry::make('position')
                            ->label('Position'),
                        IconEntry::make('is_afk')
                            ->boolean(),
                        TextEntry::make('started_at')
                            ->dateTime(),
                        TextEntry::make('completed_at')
                            ->dateTime()
                            ->placeholder('In progress'),
                    ]),

                InfolistSections::timestamps(),
            ]);
    }
}
