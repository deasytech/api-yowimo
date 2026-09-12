<?php

namespace App\Filament\Resources\Packs\Schemas;

use App\Enums\PackCategory;
use App\Filament\Support\BadgeColors;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class PackInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Basic Info')
                    ->icon(Heroicon::OutlinedIdentification)
                    ->columns(2)
                    ->schema([
                        TextEntry::make('id'),
                        TextEntry::make('gameType.name')
                            ->label('Game type'),
                        TextEntry::make('name'),
                        TextEntry::make('slug')
                            ->copyable(),
                        TextEntry::make('emoji'),
                        TextEntry::make('tag'),
                        TextEntry::make('category')
                            ->badge()
                            ->color(fn (PackCategory $state) => BadgeColors::packCategory($state)),
                    ]),

                Section::make('Description & Pricing')
                    ->icon(Heroicon::OutlinedBanknotes)
                    ->columns(2)
                    ->schema([
                        TextEntry::make('description')
                            ->columnSpanFull(),
                        TextEntry::make('price')
                            ->numeric()
                            ->suffix(' tokens'),
                        TextEntry::make('truths_count'),
                        TextEntry::make('dares_count'),
                        TextEntry::make('cards_count'),
                    ]),

                Section::make('Appearance')
                    ->icon(Heroicon::OutlinedPhoto)
                    ->schema([
                        ImageEntry::make('cover_image_url')
                            ->label('Cover image'),
                    ]),

                Section::make('Visibility & Ordering')
                    ->icon(Heroicon::OutlinedEye)
                    ->columns(3)
                    ->schema([
                        IconEntry::make('is_featured')
                            ->boolean(),
                        IconEntry::make('is_active')
                            ->boolean(),
                        TextEntry::make('sort_order'),
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
