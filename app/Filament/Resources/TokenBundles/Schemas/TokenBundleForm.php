<?php

namespace App\Filament\Resources\TokenBundles\Schemas;

use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class TokenBundleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Basic Info')
                    ->description('What this bundle is called, how many tokens it grants, and its price. Priced in Naira by default; add a USD price only for buyers confirmed to be outside Nigeria.')
                    ->icon(Heroicon::OutlinedBanknotes)
                    ->columns(2)
                    ->schema([
                        TextInput::make('slug')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255)
                            ->prefixIcon(Heroicon::OutlinedHashtag),
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('tokens')
                            ->numeric()
                            ->required(),
                        TextInput::make('price')
                            ->label('Price')
                            ->numeric()
                            ->required()
                            ->prefixIcon(Heroicon::OutlinedBanknotes)
                            ->helperText('The default price, charged to everyone unless overridden below.'),
                        TextInput::make('currency')
                            ->default('NGN')
                            ->maxLength(3)
                            ->required(),
                        TextInput::make('price_usd')
                            ->label('Price (USD)')
                            ->numeric()
                            ->prefixIcon(Heroicon::OutlinedCurrencyDollar)
                            ->helperText('Charged instead of the default price, only to buyers whose profile confirms a country other than Nigeria. Leave blank to charge them the default price too.'),
                        TextInput::make('badge')
                            ->maxLength(255),
                    ]),

                Section::make('Appearance')
                    ->icon(Heroicon::OutlinedPhoto)
                    ->collapsible()
                    ->schema([
                        TagsInput::make('gradient')
                            ->helperText('Hex color stops, e.g. #7A1EFF')
                            ->columnSpanFull(),
                    ]),

                Section::make('Visibility & Ordering')
                    ->icon(Heroicon::OutlinedEye)
                    ->columns(3)
                    ->schema([
                        Toggle::make('is_featured')
                            ->helperText('Shown in the featured bundles list.'),
                        Toggle::make('is_active')
                            ->default(true)
                            ->helperText('Inactive bundles are hidden from players.'),
                        TextInput::make('sort_order')
                            ->numeric()
                            ->default(0)
                            ->helperText('Lower numbers appear first.'),
                    ]),
            ]);
    }
}
