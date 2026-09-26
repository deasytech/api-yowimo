<?php

namespace App\Filament\Resources\PaymentMethods\Schemas;

use App\Filament\Support\InfolistSections;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class PaymentMethodInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Card')
                    ->icon(Heroicon::OutlinedCreditCard)
                    ->columns(2)
                    ->schema([
                        TextEntry::make('id'),
                        TextEntry::make('user.username')
                            ->label('User'),
                        TextEntry::make('provider'),
                        TextEntry::make('card_type')
                            ->placeholder('—'),
                        TextEntry::make('last4')
                            ->label('Last 4'),
                        TextEntry::make('exp_month')
                            ->label('Expires')
                            ->state(fn ($record): ?string => ($record->exp_month && $record->exp_year) ? "{$record->exp_month}/{$record->exp_year}" : null)
                            ->placeholder('—'),
                        TextEntry::make('bank')
                            ->placeholder('—'),
                        IconEntry::make('is_default')
                            ->boolean(),
                    ]),

                InfolistSections::timestamps(),
            ]);
    }
}
