<?php

namespace App\Filament\Resources\CardReports;

use App\Filament\Resources\CardReports\Pages\ListCardReports;
use App\Filament\Resources\CardReports\Pages\ViewCardReport;
use App\Filament\Resources\CardReports\Schemas\CardReportInfolist;
use App\Filament\Resources\CardReports\Tables\CardReportsTable;
use App\Models\CardReport;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class CardReportResource extends Resource
{
    protected static ?string $model = CardReport::class;

    protected static ?string $navigationLabel = 'Card Reports';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-flag';

    protected static string|UnitEnum|null $navigationGroup = 'Gameplay';

    protected static ?int $navigationSort = 1;

    public static function infolist(Schema $schema): Schema
    {
        return CardReportInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CardReportsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    /**
     * Reports are logged by players via the API — this panel is read-only
     * review access, never a write path.
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
            'index' => ListCardReports::route('/'),
            'view' => ViewCardReport::route('/{record}'),
        ];
    }
}
