<?php

namespace App\Filament\Resources\GameTypes\Schemas;

use App\Enums\GameIntensity;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class GameTypeForm
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
                TextInput::make('emoji')
                    ->maxLength(16),
                TextInput::make('tagline')
                    ->maxLength(255),
                TextInput::make('audience')
                    ->maxLength(255),
                Select::make('intensity')
                    ->options([
                        GameIntensity::Chill->value => 'Chill',
                        GameIntensity::Medium->value => 'Medium',
                        GameIntensity::Wild->value => 'Wild',
                    ])
                    ->required(),
                TextInput::make('cost')
                    ->numeric()
                    ->default(0)
                    ->required(),
                TextInput::make('image_url')
                    ->maxLength(255),
                TagsInput::make('gradient')
                    ->helperText('Hex color stops, e.g. #7A1EFF')
                    ->columnSpanFull(),
                Toggle::make('is_active')
                    ->default(true),
                TextInput::make('sort_order')
                    ->numeric()
                    ->default(0),
            ]);
    }
}
