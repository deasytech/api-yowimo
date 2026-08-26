<?php

namespace App\Filament\Resources\GameTypes\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class GameTypeInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('id'),
                TextEntry::make('emoji'),
                TextEntry::make('name'),
                TextEntry::make('slug'),
                TextEntry::make('tagline'),
                TextEntry::make('audience'),
                TextEntry::make('intensity')
                    ->badge(),
                TextEntry::make('cost'),
                TextEntry::make('image_url'),
                TextEntry::make('is_active'),
                TextEntry::make('sort_order'),
                TextEntry::make('created_at')
                    ->dateTime(),
                TextEntry::make('updated_at')
                    ->dateTime(),
            ]);
    }
}
