<?php

namespace App\Filament\Resources\PackCards\Schemas;

use App\Enums\PackCardKind;
use App\Models\Pack;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class PackCardForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Card Details')
                    ->description('The truth or dare prompt shown to players, and which pack it belongs to.')
                    ->icon(Heroicon::OutlinedRectangleStack)
                    ->columns(2)
                    ->schema([
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
                            ->rows(3)
                            ->columnSpanFull(),
                        TextInput::make('position')
                            ->numeric()
                            ->default(0)
                            ->required()
                            ->helperText('Display order among cards of the same kind.'),
                        Toggle::make('is_preview')
                            ->helperText("Visible to players who haven't purchased this pack yet."),
                    ]),
            ]);
    }
}
