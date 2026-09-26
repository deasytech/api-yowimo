<?php

namespace App\Filament\Resources\Wallets\Schemas;

use App\Filament\Support\InfolistSections;
use App\Models\Wallet;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class WalletInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Wallet')
                    ->icon(Heroicon::OutlinedWallet)
                    ->columns(2)
                    ->schema([
                        TextEntry::make('id'),
                        TextEntry::make('user.username')
                            ->label('User'),
                        TextEntry::make('balance')
                            ->label('Cached balance')
                            ->numeric()
                            ->suffix(' tokens'),
                        TextEntry::make('ledger_balance')
                            ->label('Ledger balance')
                            ->state(fn (Wallet $record): int => $record->ledgerBalance())
                            ->numeric()
                            ->suffix(' tokens')
                            ->helperText('Derived from the transaction ledger — must match the cached balance.'),
                        TextEntry::make('currency'),
                    ]),

                InfolistSections::timestamps(),
            ]);
    }
}
