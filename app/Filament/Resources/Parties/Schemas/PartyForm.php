<?php

namespace App\Filament\Resources\Parties\Schemas;

use App\Enums\PartyMode;
use App\Enums\PartyStatus;
use App\Enums\PartyVisibility;
use App\Filament\Support\ImageUploadField;
use App\Models\GameType;
use App\Models\Pack;
use App\Models\Party;
use App\Models\User;
use Closure;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
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
                            ->searchable()
                            ->live()
                            ->disabled(fn (?Party $record) => $record?->gameSessions()->exists() ?? false)
                            ->afterStateUpdated(fn (Set $set) => $set('pack_id', null))
                            ->helperText('Locked once a game session has started for this party.'),
                        Select::make('pack_id')
                            ->label('Pack')
                            ->options(fn (Get $get) => Pack::query()
                                ->when($get('game_type_id'), fn ($query, $gameTypeId) => $query->where('game_type_id', $gameTypeId))
                                ->pluck('name', 'id'))
                            ->searchable()
                            ->disabled(fn (?Party $record) => $record?->gameSessions()->exists() ?? false)
                            ->rule(fn (Get $get): Closure => function (string $attribute, mixed $value, Closure $fail) use ($get) {
                                if (blank($value) || blank($get('game_type_id'))) {
                                    return;
                                }

                                if (! Pack::where('id', $value)->where('game_type_id', $get('game_type_id'))->exists()) {
                                    $fail('The selected pack does not belong to the selected game type.');
                                }
                            })
                            ->helperText('Only shows packs for the selected game type; locked once a game session has started.'),
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
                            ->rules(['array', 'max:5'])
                            ->nestedRecursiveRules(['max:20'])
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
