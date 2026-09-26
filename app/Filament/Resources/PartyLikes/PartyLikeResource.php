<?php

namespace App\Filament\Resources\PartyLikes;

use App\Filament\Resources\PartyLikes\Pages\ListPartyLikes;
use App\Filament\Resources\PartyLikes\Pages\ViewPartyLike;
use App\Filament\Resources\PartyLikes\Schemas\PartyLikeInfolist;
use App\Filament\Resources\PartyLikes\Tables\PartyLikesTable;
use App\Models\PartyLike;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class PartyLikeResource extends Resource
{
    protected static ?string $model = PartyLike::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-hand-thumb-up';

    protected static string|UnitEnum|null $navigationGroup = 'Community';

    protected static ?int $navigationSort = 5;

    public static function infolist(Schema $schema): Schema
    {
        return PartyLikeInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PartyLikesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    /**
     * Likes are created/removed through the API only — this resource exists
     * purely so admins can audit engagement.
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
            'index' => ListPartyLikes::route('/'),
            'view' => ViewPartyLike::route('/{record}'),
        ];
    }
}
