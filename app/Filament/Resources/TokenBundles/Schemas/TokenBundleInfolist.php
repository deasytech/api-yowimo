<?php

namespace App\Filament\Resources\TokenBundles\Schemas;

use App\Filament\Support\InfolistSections;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class TokenBundleInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Basic Info')
                    ->icon(Heroicon::OutlinedBanknotes)
                    ->columns(2)
                    ->schema([
                        TextEntry::make('id'),
                        TextEntry::make('name'),
                        TextEntry::make('slug')
                            ->copyable(),
                        TextEntry::make('tokens')
                            ->numeric()
                            ->suffix(' tokens'),
                        TextEntry::make('price')
                            ->money(fn ($record) => $record->currency ?? 'NGN'),
                        TextEntry::make('currency'),
                        TextEntry::make('price_usd')
                            ->label('Price (USD)')
                            ->money('USD')
                            ->placeholder('Not set — non-Nigerian buyers are charged the price above too.'),
                        TextEntry::make('badge'),
                    ]),

                InfolistSections::visibilityAndOrdering(),

                InfolistSections::timestamps(),
            ]);
    }
}
