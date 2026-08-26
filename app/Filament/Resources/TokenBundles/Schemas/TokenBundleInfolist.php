<?php

namespace App\Filament\Resources\TokenBundles\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class TokenBundleInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('id'),
                TextEntry::make('name'),
                TextEntry::make('slug'),
                TextEntry::make('tokens'),
                TextEntry::make('price'),
                TextEntry::make('currency'),
                TextEntry::make('badge'),
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
