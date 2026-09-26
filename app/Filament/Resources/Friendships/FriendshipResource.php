<?php

namespace App\Filament\Resources\Friendships;

use App\Filament\Resources\Friendships\Pages\ListFriendships;
use App\Filament\Resources\Friendships\Pages\ViewFriendship;
use App\Filament\Resources\Friendships\Schemas\FriendshipInfolist;
use App\Filament\Resources\Friendships\Tables\FriendshipsTable;
use App\Models\Friendship;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class FriendshipResource extends Resource
{
    protected static ?string $model = Friendship::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-heart';

    protected static string|UnitEnum|null $navigationGroup = 'Community';

    protected static ?int $navigationSort = 4;

    public static function infolist(Schema $schema): Schema
    {
        return FriendshipInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return FriendshipsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    /**
     * FriendshipPolicy's abilities (create/accept/reject/cancel/remove) all
     * model the end-user API, where only the participants may act — none of
     * that applies to the panel, which is reachability-gated by
     * User::canAccessPanel() (is_admin) and only ever displays records.
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
            'index' => ListFriendships::route('/'),
            'view' => ViewFriendship::route('/{record}'),
        ];
    }
}
