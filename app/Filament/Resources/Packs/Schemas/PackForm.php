<?php

namespace App\Filament\Resources\Packs\Schemas;

use App\Enums\PackCategory;
use App\Models\GameType;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class PackForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('game_type_id')
                    ->label('Game type')
                    ->options(fn () => GameType::query()->pluck('name', 'id'))
                    ->searchable()
                    ->required(),
                TextInput::make('slug')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(255),
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                TextInput::make('emoji')
                    ->maxLength(16),
                TextInput::make('tag')
                    ->maxLength(255),
                Select::make('category')
                    ->options([
                        PackCategory::Spicy->value => 'Spicy',
                        PackCategory::Couples->value => 'Couples',
                        PackCategory::Family->value => 'Family',
                        PackCategory::Corporate->value => 'Corporate',
                        PackCategory::Limited->value => 'Limited',
                    ])
                    ->required(),
                Textarea::make('description')
                    ->columnSpanFull(),
                TextInput::make('price')
                    ->numeric()
                    ->default(0)
                    ->required(),
                TextInput::make('truths_count')
                    ->numeric()
                    ->default(0)
                    ->required(),
                TextInput::make('dares_count')
                    ->numeric()
                    ->default(0)
                    ->required(),
                TextInput::make('cards_count')
                    ->numeric()
                    ->default(0)
                    ->required(),
                TextInput::make('cover_image_url')
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
