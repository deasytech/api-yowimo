<?php

namespace App\Filament\Resources\XpTransactions\Schemas;

use App\Enums\XpTransactionType;
use App\Filament\Support\BadgeColors;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class XpTransactionInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('XP Transaction')
                    ->icon(Heroicon::OutlinedBolt)
                    ->columns(2)
                    ->schema([
                        TextEntry::make('id'),
                        TextEntry::make('user.username')
                            ->label('User'),
                        TextEntry::make('type')
                            ->badge()
                            ->color(fn (XpTransactionType $state) => BadgeColors::xpTransactionType($state)),
                        TextEntry::make('amount')
                            ->numeric()
                            ->suffix(' XP'),
                        TextEntry::make('game_session_id')
                            ->label('Session')
                            ->placeholder('None'),
                        TextEntry::make('idempotency_key')
                            ->copyable()
                            ->placeholder('None'),
                        TextEntry::make('reference_type')
                            ->label('Reference')
                            ->state(fn ($record): ?string => $record->reference_type
                                ? "{$record->reference_type} #{$record->reference_id}"
                                : null)
                            ->placeholder('None'),
                    ]),

                Section::make('Timestamps')
                    ->icon(Heroicon::OutlinedClock)
                    ->schema([
                        TextEntry::make('created_at')
                            ->dateTime(),
                    ]),
            ]);
    }
}
