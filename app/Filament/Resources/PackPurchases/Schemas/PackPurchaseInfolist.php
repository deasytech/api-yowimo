<?php

namespace App\Filament\Resources\PackPurchases\Schemas;

use App\Filament\Support\InfolistSections;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class PackPurchaseInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Purchase')
                    ->icon(Heroicon::OutlinedShoppingCart)
                    ->columns(2)
                    ->schema([
                        TextEntry::make('id'),
                        TextEntry::make('user.username')
                            ->label('User'),
                        TextEntry::make('pack.name')
                            ->label('Pack'),
                        TextEntry::make('wallet_transaction_id')
                            ->label('Ledger entry')
                            ->placeholder('No linked ledger entry'),
                    ]),

                InfolistSections::timestamps(),
            ]);
    }
}
