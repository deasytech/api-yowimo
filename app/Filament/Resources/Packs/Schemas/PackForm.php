<?php

namespace App\Filament\Resources\Packs\Schemas;

use App\Enums\PackCategory;
use App\Filament\Support\ImageUploadField;
use App\Models\GameType;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class PackForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Basic Info')
                    ->description('What this pack is called and where it fits in the catalog.')
                    ->icon(Heroicon::OutlinedIdentification)
                    ->columns(2)
                    ->schema([
                        Select::make('game_type_id')
                            ->label('Game type')
                            ->options(fn () => GameType::query()->pluck('name', 'id'))
                            ->searchable()
                            ->required(),
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
                    ]),

                Section::make('Description & Pricing')
                    ->icon(Heroicon::OutlinedBanknotes)
                    ->columns(2)
                    ->schema([
                        Textarea::make('description')
                            ->rows(3)
                            ->columnSpanFull(),
                        TextInput::make('price')
                            ->numeric()
                            ->default(0)
                            ->required()
                            ->suffix('tokens'),
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
                            ->required()
                            ->helperText('Total cards in this pack.'),
                    ]),

                Section::make('Appearance')
                    ->icon(Heroicon::OutlinedPhoto)
                    ->collapsible()
                    ->schema([
                        ImageUploadField::make('cover_image_url', 'packs'),
                        TagsInput::make('gradient')
                            ->helperText('Hex color stops, e.g. #7A1EFF')
                            ->columnSpanFull(),
                    ]),

                Section::make('Visibility & Ordering')
                    ->icon(Heroicon::OutlinedEye)
                    ->columns(3)
                    ->schema([
                        Toggle::make('is_featured')
                            ->helperText('Shown in the featured packs list.'),
                        Toggle::make('is_active')
                            ->default(true)
                            ->helperText('Inactive packs are hidden from players.'),
                        TextInput::make('sort_order')
                            ->numeric()
                            ->default(0)
                            ->helperText('Lower numbers appear first.'),
                    ]),
            ]);
    }
}
