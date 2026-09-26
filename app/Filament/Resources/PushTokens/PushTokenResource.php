<?php

namespace App\Filament\Resources\PushTokens;

use App\Filament\Resources\PushTokens\Pages\ListPushTokens;
use App\Filament\Resources\PushTokens\Pages\ViewPushToken;
use App\Filament\Resources\PushTokens\Schemas\PushTokenInfolist;
use App\Filament\Resources\PushTokens\Tables\PushTokensTable;
use App\Models\PushToken;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class PushTokenResource extends Resource
{
    protected static ?string $model = PushToken::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-device-phone-mobile';

    protected static string|UnitEnum|null $navigationGroup = 'System';

    protected static ?int $navigationSort = 2;

    public static function infolist(Schema $schema): Schema
    {
        return PushTokenInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PushTokensTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    /**
     * Devices register/refresh tokens through the API — the panel only
     * inspects registrations for debugging delivery issues.
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
            'index' => ListPushTokens::route('/'),
            'view' => ViewPushToken::route('/{record}'),
        ];
    }
}
