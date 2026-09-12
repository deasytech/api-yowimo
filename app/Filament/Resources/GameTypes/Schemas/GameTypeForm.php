<?php

namespace App\Filament\Resources\GameTypes\Schemas;

use App\Enums\GameIntensity;
use App\Filament\Support\ImageUploadField;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class GameTypeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Identity')
                    ->description('How this game type is named and introduced to players.')
                    ->icon(Heroicon::OutlinedIdentification)
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
                        TextInput::make('emoji')
                            ->maxLength(16),
                        TextInput::make('tagline')
                            ->maxLength(255),
                        TextInput::make('audience')
                            ->maxLength(255)
                            ->columnSpanFull(),
                    ]),

                Section::make('Gameplay')
                    ->description('Intensity level and token cost to play.')
                    ->icon(Heroicon::OutlinedAdjustmentsHorizontal)
                    ->columns(2)
                    ->schema([
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
                            ->required()
                            ->prefixIcon(Heroicon::OutlinedCurrencyDollar),
                    ]),

                Section::make('Appearance')
                    ->description('Cover image and gradient shown in the app.')
                    ->icon(Heroicon::OutlinedPhoto)
                    ->collapsible()
                    ->schema([
                        ImageUploadField::make('image_url', 'game-types'),
                        TagsInput::make('gradient')
                            ->helperText('Hex color stops, e.g. #7A1EFF')
                            ->columnSpanFull(),
                    ]),

                Section::make('Visibility & Ordering')
                    ->icon(Heroicon::OutlinedEye)
                    ->columns(2)
                    ->schema([
                        Toggle::make('is_active')
                            ->default(true)
                            ->helperText('Inactive game types are hidden from players.'),
                        TextInput::make('sort_order')
                            ->numeric()
                            ->default(0)
                            ->helperText('Lower numbers appear first.'),
                    ]),
            ]);
    }
}
