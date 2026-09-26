<?php

namespace App\Filament\Resources\PartyMembers;

use App\Filament\Resources\PartyMembers\Pages\ListPartyMembers;
use App\Filament\Resources\PartyMembers\Pages\ViewPartyMember;
use App\Filament\Resources\PartyMembers\Schemas\PartyMemberInfolist;
use App\Filament\Resources\PartyMembers\Tables\PartyMembersTable;
use App\Models\PartyMember;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class PartyMemberResource extends Resource
{
    protected static ?string $model = PartyMember::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-user-group';

    protected static string|UnitEnum|null $navigationGroup = 'Community';

    protected static ?int $navigationSort = 3;

    public static function infolist(Schema $schema): Schema
    {
        return PartyMemberInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PartyMembersTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    /**
     * Membership changes (join/leave/remove) are driven by the API, which
     * also broadcasts them to the party in real time — this resource is
     * audit visibility only, never a write path.
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
            'index' => ListPartyMembers::route('/'),
            'view' => ViewPartyMember::route('/{record}'),
        ];
    }
}
