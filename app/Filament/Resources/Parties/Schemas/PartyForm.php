<?php

namespace App\Filament\Resources\Parties\Schemas;

use App\Enums\PartyMode;
use App\Enums\PartyStatus;
use App\Enums\PartyVisibility;
use App\Filament\Support\ImageUploadField;
use App\Models\GameType;
use App\Models\Pack;
use App\Models\User;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class PartyForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Basic Info')
                    ->icon(Heroicon::OutlinedIdentification)
                    ->columns(2)
                    ->schema([
                        Select::make('host_id')
                            ->label('Host')
                            ->options(fn () => User::query()->whereNotNull('username')->pluck('username', 'id'))
                            ->searchable()
                            ->required()
                            ->disabledOn('edit')
                            ->helperText('Cannot be changed after the party is created.'),
                        TextInput::make('title')
                            ->required()
                            ->maxLength(100),
                        Textarea::make('description')
                            ->rows(3)
                            ->maxLength(1000)
                            ->columnSpanFull(),
                        Select::make('game_type_id')
                            ->label('Game type')
                            ->options(fn () => GameType::query()->pluck('name', 'id'))
                            ->searchable(),
                        Select::make('pack_id')
                            ->label('Pack')
                            ->options(fn () => Pack::query()->pluck('name', 'id'))
                            ->searchable(),
                        TextInput::make('room_code')
                            ->label('Room code')
                            ->maxLength(10)
                            ->disabled()
                            ->visibleOn('edit')
                            ->helperText('Generated automatically on create; cannot be changed.'),
                    ]),

                Section::make('Status & Settings')
                    ->icon(Heroicon::OutlinedAdjustmentsHorizontal)
                    ->columns(3)
                    ->schema([
                        Select::make('mode')
                            ->options([
                                PartyMode::Online->value => 'Online',
                                PartyMode::Hybrid->value => 'Hybrid',
                                PartyMode::InPerson->value => 'In person',
                            ])
                            ->required(),
                        Select::make('visibility')
                            ->options([
                                PartyVisibility::Public->value => 'Public',
                                PartyVisibility::Private->value => 'Private',
                            ])
                            ->required(),
                        Select::make('status')
                            ->options([
                                PartyStatus::Draft->value => 'Draft',
                                PartyStatus::Scheduled->value => 'Scheduled',
                                PartyStatus::Live->value => 'Live',
                                PartyStatus::Ended->value => 'Ended',
                                PartyStatus::Cancelled->value => 'Cancelled',
                            ])
                            ->default(PartyStatus::Draft->value)
                            ->required(),
                        TextInput::make('max_players')
                            ->numeric()
                            ->default(8)
                            ->minValue(2)
                            ->maxValue(200)
                            ->required(),
                        DateTimePicker::make('starts_at'),
                    ]),

                Section::make('Location')
                    ->description('Only relevant for hybrid/in-person parties.')
                    ->icon(Heroicon::OutlinedMapPin)
                    ->collapsible()
                    ->schema([
                        Fieldset::make('Location')
                            ->columns(2)
                            ->schema([
                                TextInput::make('location.venue_name')
                                    ->label('Venue name')
                                    ->maxLength(150),
                                TextInput::make('location.address')
                                    ->label('Address')
                                    ->maxLength(255),
                                TextInput::make('location.latitude')
                                    ->numeric(),
                                TextInput::make('location.longitude')
                                    ->numeric(),
                            ]),
                    ]),

                Section::make('Appearance')
                    ->icon(Heroicon::OutlinedPhoto)
                    ->collapsible()
                    ->schema([
                        ImageUploadField::make('cover_image_url', 'parties'),
                        TagsInput::make('tags')
                            ->helperText('Up to 5 tags, max 20 characters each.')
                            ->columnSpanFull(),
                        TagsInput::make('gradient')
                            ->helperText('Hex color stops, e.g. #7A1EFF')
                            ->columnSpanFull(),
                    ]),

                Section::make('Sponsorship')
                    ->icon(Heroicon::OutlinedGift)
                    ->columns(2)
                    ->collapsible()
                    ->schema([
                        Toggle::make('is_sponsored')
                            ->live(),
                        TextInput::make('sponsor_name')
                            ->maxLength(255)
                            ->visible(fn ($get) => $get('is_sponsored')),
                    ]),
            ]);
    }
}
