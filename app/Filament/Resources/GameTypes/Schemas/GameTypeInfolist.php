<?php

namespace App\Filament\Resources\GameTypes\Schemas;

use App\Enums\GameIntensity;
use App\Filament\Support\BadgeColors;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class GameTypeInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Identity')
                    ->icon(Heroicon::OutlinedIdentification)
                    ->columns(2)
                    ->schema([
                        TextEntry::make('id'),
                        TextEntry::make('name'),
                        TextEntry::make('slug')
                            ->copyable(),
                        TextEntry::make('emoji'),
                        TextEntry::make('tagline'),
                        TextEntry::make('audience')
                            ->columnSpanFull(),
                    ]),

                Section::make('Gameplay')
                    ->icon(Heroicon::OutlinedAdjustmentsHorizontal)
                    ->columns(2)
                    ->schema([
                        TextEntry::make('intensity')
                            ->badge()
                            ->color(fn (GameIntensity $state) => BadgeColors::gameIntensity($state)),
                        TextEntry::make('cost')
                            ->numeric()
                            ->suffix(' tokens'),
                        TextEntry::make('defaultPack.name')
                            ->label('Default deck')
                            ->placeholder('Cheapest active deck'),
                    ]),

                Section::make('Appearance')
                    ->icon(Heroicon::OutlinedPhoto)
                    ->schema([
                        ImageEntry::make('image_url')
                            ->label('Image')
                            // Pinned explicitly: without it Filament resolves
                            // against FILESYSTEM_DISK (local), not where
                            // uploads actually land — masked until now only
                            // because every stored value used to be an
                            // already-absolute URL.
                            ->disk('public')
                            ->circular(),
                    ]),

                Section::make('Visibility & Ordering')
                    ->icon(Heroicon::OutlinedEye)
                    ->columns(2)
                    ->schema([
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
