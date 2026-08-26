<?php

namespace App\Filament\Resources\Packs\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class PackInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('id'),
                TextEntry::make('emoji'),
                TextEntry::make('name'),
                TextEntry::make('slug'),
                TextEntry::make('gameType.name')
                    ->label('Game type'),
                TextEntry::make('tag'),
                TextEntry::make('category')
                    ->badge(),
                TextEntry::make('description')
                    ->columnSpanFull(),
                TextEntry::make('price'),
                TextEntry::make('truths_count'),
                TextEntry::make('dares_count'),
                TextEntry::make('cards_count'),
                TextEntry::make('is_featured'),
                TextEntry::make('is_active'),
                TextEntry::make('sort_order'),
                TextEntry::make('created_at')
                    ->dateTime(),
                TextEntry::make('updated_at')
                    ->dateTime(),
            ]);
    }
}
