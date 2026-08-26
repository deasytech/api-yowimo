<?php

namespace App\Filament\Resources\WalletTransactions\Schemas;

use Filament\Infolists\Components\KeyValueEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class WalletTransactionInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('id'),
                TextEntry::make('wallet.user.username')
                    ->label('User'),
                TextEntry::make('type')
                    ->badge(),
                TextEntry::make('amount'),
                TextEntry::make('balance_after'),
                TextEntry::make('reference_type'),
                TextEntry::make('reference_id'),
                TextEntry::make('idempotency_key'),
                TextEntry::make('description')
                    ->columnSpanFull(),
                KeyValueEntry::make('metadata')
                    ->columnSpanFull(),
                TextEntry::make('created_at')
                    ->dateTime(),
            ]);
    }
}
