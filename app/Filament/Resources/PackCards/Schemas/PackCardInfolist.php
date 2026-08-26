<?php

namespace App\Filament\Resources\PackCards\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class PackCardInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('id'),
                TextEntry::make('pack.name')
                    ->label('Pack'),
                TextEntry::make('kind')
                    ->badge(),
                TextEntry::make('text')
                    ->columnSpanFull(),
                TextEntry::make('position'),
                TextEntry::make('is_preview'),
                TextEntry::make('created_at')
                    ->dateTime(),
                TextEntry::make('updated_at')
                    ->dateTime(),
            ]);
    }
}
