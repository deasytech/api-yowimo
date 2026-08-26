<?php

namespace App\Filament\Resources\GameTypes;

use App\Filament\Resources\GameTypes\Pages\CreateGameType;
use App\Filament\Resources\GameTypes\Pages\EditGameType;
use App\Filament\Resources\GameTypes\Pages\ListGameTypes;
use App\Filament\Resources\GameTypes\Pages\ViewGameType;
use App\Filament\Resources\GameTypes\Schemas\GameTypeForm;
use App\Filament\Resources\GameTypes\Schemas\GameTypeInfolist;
use App\Filament\Resources\GameTypes\Tables\GameTypesTable;
use App\Models\GameType;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class GameTypeResource extends Resource
{
    protected static ?string $model = GameType::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return GameTypeForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return GameTypeInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return GameTypesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListGameTypes::route('/'),
            'create' => CreateGameType::route('/create'),
            'view' => ViewGameType::route('/{record}'),
            'edit' => EditGameType::route('/{record}/edit'),
        ];
    }
}
