<?php

namespace App\Filament\Resources\CardReports\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class CardReportInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Report')
                    ->icon(Heroicon::OutlinedFlag)
                    ->columns(2)
                    ->schema([
                        TextEntry::make('id'),
                        TextEntry::make('reporter.username')
                            ->label('Reporter'),
                        TextEntry::make('packCard.text')
                            ->label('Card')
                            ->columnSpanFull(),
                        TextEntry::make('reason')
                            ->badge(),
                        TextEntry::make('note')
                            ->columnSpanFull()
                            ->placeholder('—'),
                    ]),

                Section::make('Timestamps')
                    ->icon(Heroicon::OutlinedClock)
                    ->collapsible()
                    ->schema([
                        TextEntry::make('created_at')
                            ->dateTime(),
                    ]),
            ]);
    }
}
