<?php

namespace App\Filament\Resources\Rounds\Schemas;

use App\Filament\Support\InfolistSections;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class RoundInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Round')
                    ->icon(Heroicon::OutlinedArrowPath)
                    ->columns(2)
                    ->schema([
                        TextEntry::make('id'),
                        TextEntry::make('game_session_id')
                            ->label('Session'),
                        TextEntry::make('number')
                            ->label('Round'),
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
