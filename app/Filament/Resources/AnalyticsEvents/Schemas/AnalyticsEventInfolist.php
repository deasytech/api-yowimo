<?php

namespace App\Filament\Resources\AnalyticsEvents\Schemas;

use Filament\Infolists\Components\KeyValueEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class AnalyticsEventInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Event')
                    ->icon(Heroicon::OutlinedChartBarSquare)
                    ->columns(2)
                    ->schema([
                        TextEntry::make('id'),
                        TextEntry::make('event'),
                        TextEntry::make('user.username')
                            ->label('User')
                            ->placeholder('Guest'),
                        TextEntry::make('country')
                            ->placeholder('—'),
                        TextEntry::make('device')
                            ->placeholder('—'),
                        TextEntry::make('ip')
                            ->placeholder('—'),
                        KeyValueEntry::make('payload')
                            ->label('Payload')
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
