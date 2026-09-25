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
                    ->description('What this bundle is called, how many tokens it grants, and its price.')
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
                            ->numeric()
                            ->required()
                            ->prefixIcon(Heroicon::OutlinedCurrencyDollar),
                        TextInput::make('currency')
                            ->default('USD')
                            ->maxLength(3)
                            ->required(),
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
