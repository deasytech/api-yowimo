<?php

namespace App\Filament\Resources\Turns;

use App\Filament\Resources\Turns\Pages\ListTurns;
use App\Filament\Resources\Turns\Pages\ViewTurn;
use App\Filament\Resources\Turns\Schemas\TurnInfolist;
use App\Filament\Resources\Turns\Tables\TurnsTable;
use App\Models\Turn;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class TurnResource extends Resource
{
    protected static ?string $model = Turn::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-forward';

    protected static string|UnitEnum|null $navigationGroup = 'Gameplay';

    protected static ?int $navigationSort = 3;

    public static function infolist(Schema $schema): Schema
    {
        return TurnInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TurnsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    /**
     * TurnPolicy only models the API's vote() ability — no view/write
     * abilities exist for it, and turns themselves are advanced by
     * GameSessionService. The panel is audit visibility only.
     */
    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTurns::route('/'),
            'view' => ViewTurn::route('/{record}'),
        ];
    }
}
