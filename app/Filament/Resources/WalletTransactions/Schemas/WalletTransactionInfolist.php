<?php

namespace App\Filament\Resources\WalletTransactions\Schemas;

use App\Enums\WalletTransactionType;
use App\Filament\Support\BadgeColors;
use Filament\Infolists\Components\KeyValueEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class WalletTransactionInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Transaction')
                    ->icon(Heroicon::OutlinedBanknotes)
                    ->columns(2)
                    ->schema([
                        TextEntry::make('id'),
                        TextEntry::make('wallet.user.username')
                            ->label('User'),
                        TextEntry::make('type')
                            ->badge()
                            ->color(fn (WalletTransactionType $state) => BadgeColors::walletTransactionType($state)),
                        TextEntry::make('amount')
                            ->numeric()
                            ->suffix(' tokens'),
                        TextEntry::make('balance_after')
                            ->numeric()
                            ->suffix(' tokens'),
                        TextEntry::make('description')
                            ->columnSpanFull(),
                    ]),

                Section::make('Reference & Idempotency')
                    ->icon(Heroicon::OutlinedHashtag)
                    ->columns(2)
                    ->collapsible()
                    ->schema([
                        TextEntry::make('reference_type'),
                        TextEntry::make('reference_id'),
                        TextEntry::make('idempotency_key')
                            ->columnSpanFull()
                            ->copyable(),
                        KeyValueEntry::make('metadata')
                            ->columnSpanFull(),
                    ]),

                Section::make('Timestamps')
                    ->icon(Heroicon::OutlinedClock)
                    ->collapsible()
                    ->schema([
                        TextEntry::make('created_at')
                            ->dateTime(),
                    ]),
            ]);
    }
}
