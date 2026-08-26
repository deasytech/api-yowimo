<?php

namespace App\Filament\Resources\TokenBundles\Schemas;

use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class TokenBundleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('slug')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(255),
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                TextInput::make('tokens')
                    ->numeric()
                    ->required(),
                TextInput::make('price')
                    ->numeric()
                    ->required(),
                TextInput::make('currency')
                    ->default('USD')
                    ->maxLength(3)
                    ->required(),
                TextInput::make('badge')
                    ->maxLength(255),
                TagsInput::make('gradient')
                    ->helperText('Hex color stops, e.g. #7A1EFF')
                    ->columnSpanFull(),
                Toggle::make('is_featured'),
                Toggle::make('is_active')
                    ->default(true),
                TextInput::make('sort_order')
                    ->numeric()
                    ->default(0),
            ]);
    }
}
