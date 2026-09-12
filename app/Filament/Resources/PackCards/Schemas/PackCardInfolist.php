<?php

namespace App\Filament\Resources\PackCards\Schemas;

use App\Enums\PackCardKind;
use App\Filament\Support\BadgeColors;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class PackCardInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Card Details')
                    ->icon(Heroicon::OutlinedRectangleStack)
                    ->columns(2)
                    ->schema([
                        TextEntry::make('id'),
                        TextEntry::make('pack.name')
                            ->label('Pack'),
                        TextEntry::make('kind')
                            ->badge()
                            ->color(fn (PackCardKind $state) => BadgeColors::packCardKind($state)),
                        TextEntry::make('position'),
                        TextEntry::make('text')
                            ->columnSpanFull(),
                        IconEntry::make('is_preview')
                            ->boolean(),
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
