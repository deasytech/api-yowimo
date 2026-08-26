<?php

namespace App\Filament\Resources\PackCards\Schemas;

use App\Enums\PackCardKind;
use App\Models\Pack;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class PackCardForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('pack_id')
                    ->label('Pack')
                    ->options(fn () => Pack::query()->pluck('name', 'id'))
                    ->searchable()
                    ->required(),
                Select::make('kind')
                    ->options([
                        PackCardKind::Truth->value => 'Truth',
                        PackCardKind::Dare->value => 'Dare',
                    ])
                    ->required(),
                Textarea::make('text')
                    ->required()
                    ->columnSpanFull(),
                TextInput::make('position')
                    ->numeric()
                    ->default(0)
                    ->required(),
                Toggle::make('is_preview'),
            ]);
    }
}
