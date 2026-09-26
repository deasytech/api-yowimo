<?php

namespace App\Filament\Resources\Badges\Schemas;

use App\Enums\BadgeKey;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Str;

class BadgeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Badge')
                    ->description('Reference data for the achievements listeners award.')
                    ->icon(Heroicon::OutlinedAcademicCap)
                    ->columns(2)
                    ->schema([
                        Select::make('key')
                            ->label('Key')
                            ->options(collect(BadgeKey::cases())
                                ->mapWithKeys(fn (BadgeKey $case): array => [$case->value => Str::headline($case->value)])
                                ->all())
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->disabled(fn (string $operation): bool => $operation !== 'create')
                            ->helperText('The stable key listeners award — locked once the badge exists.'),
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('icon')
                            ->maxLength(255)
                            ->helperText('Icon name, emoji, or image URL.'),
                        Textarea::make('description')
                            ->required()
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
