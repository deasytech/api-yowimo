<?php

namespace App\Filament\Resources\UserBadges;

use App\Filament\Resources\UserBadges\Pages\ListUserBadges;
use App\Filament\Resources\UserBadges\Pages\ViewUserBadge;
use App\Filament\Resources\UserBadges\Schemas\UserBadgeInfolist;
use App\Filament\Resources\UserBadges\Tables\UserBadgesTable;
use App\Models\UserBadge;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class UserBadgeResource extends Resource
{
    protected static ?string $model = UserBadge::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-star';

    protected static string|UnitEnum|null $navigationGroup = 'Progression';

    protected static ?int $navigationSort = 2;

    public static function infolist(Schema $schema): Schema
    {
        return UserBadgeInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return UserBadgesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    /**
     * Earned badges are append-only (see UserBadge::booted()) — revoking one
     * is not a supported operation, so this resource is view-only.
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
            'index' => ListUserBadges::route('/'),
            'view' => ViewUserBadge::route('/{record}'),
        ];
    }
}
