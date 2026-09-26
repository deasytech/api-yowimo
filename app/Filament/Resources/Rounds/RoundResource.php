<?php

namespace App\Filament\Resources\Rounds;

use App\Filament\Resources\Rounds\Pages\ListRounds;
use App\Filament\Resources\Rounds\Pages\ViewRound;
use App\Filament\Resources\Rounds\Schemas\RoundInfolist;
use App\Filament\Resources\Rounds\Tables\RoundsTable;
use App\Models\Round;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class RoundResource extends Resource
{
    protected static ?string $model = Round::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-arrow-path';

    protected static string|UnitEnum|null $navigationGroup = 'Gameplay';

    protected static ?int $navigationSort = 2;

    public static function infolist(Schema $schema): Schema
    {
        return RoundInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return RoundsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    /**
     * Rounds are created and completed by GameSessionService — audit only.
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
            'index' => ListRounds::route('/'),
            'view' => ViewRound::route('/{record}'),
        ];
    }
}
