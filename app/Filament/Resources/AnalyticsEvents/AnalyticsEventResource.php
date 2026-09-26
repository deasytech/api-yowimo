<?php

namespace App\Filament\Resources\AnalyticsEvents;

use App\Filament\Resources\AnalyticsEvents\Pages\ListAnalyticsEvents;
use App\Filament\Resources\AnalyticsEvents\Pages\ViewAnalyticsEvent;
use App\Filament\Resources\AnalyticsEvents\Schemas\AnalyticsEventInfolist;
use App\Filament\Resources\AnalyticsEvents\Tables\AnalyticsEventsTable;
use App\Models\AnalyticsEvent;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class AnalyticsEventResource extends Resource
{
    protected static ?string $model = AnalyticsEvent::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-chart-bar-square';

    protected static string|UnitEnum|null $navigationGroup = 'System';

    protected static ?int $navigationSort = 3;

    public static function infolist(Schema $schema): Schema
    {
        return AnalyticsEventInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AnalyticsEventsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    /**
     * The analytics stream is written once by RecordAnalyticsEvent and never
     * rewritten — this resource is pure audit access.
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
            'index' => ListAnalyticsEvents::route('/'),
            'view' => ViewAnalyticsEvent::route('/{record}'),
        ];
    }
}
