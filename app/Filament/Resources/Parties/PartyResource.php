<?php

namespace App\Filament\Resources\Parties;

use App\Filament\Resources\Parties\Pages\CreateParty;
use App\Filament\Resources\Parties\Pages\EditParty;
use App\Filament\Resources\Parties\Pages\ListParties;
use App\Filament\Resources\Parties\Pages\ViewParty;
use App\Filament\Resources\Parties\Schemas\PartyForm;
use App\Filament\Resources\Parties\Schemas\PartyInfolist;
use App\Filament\Resources\Parties\Tables\PartiesTable;
use App\Models\Party;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Auth\Access\Response;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class PartyResource extends Resource
{
    protected static ?string $model = Party::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-calendar-days';

    protected static string|UnitEnum|null $navigationGroup = 'Community';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return PartyForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return PartyInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PartiesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    /**
     * Party's model policy also governs the public API, where "update" is
     * host-only (see PartyPolicy::update()) — that restriction doesn't apply
     * here, since reaching this resource at all already requires
     * User::canAccessPanel() (is_admin). These overrides bypass the shared
     * policy for the admin panel specifically, rather than loosening it.
     */
    public static function canCreate(): bool
    {
        return true;
    }

    /**
     * The table's EditAction (and ListRecords' header actions) authorize
     * through this response — via Pages\Page::getDefaultActionAuthorizationResponse() —
     * not through canEdit(), so overriding canEdit() alone leaves the edit
     * button hidden whenever the API's host-only PartyPolicy::update()
     * denies the admin (i.e. for every party they don't host).
     * Resource::canEdit() delegates here, so both paths stay allowed.
     */
    public static function getEditAuthorizationResponse(Model $record): Response
    {
        return Response::allow();
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
            'index' => ListParties::route('/'),
            'create' => CreateParty::route('/create'),
            'view' => ViewParty::route('/{record}'),
            'edit' => EditParty::route('/{record}/edit'),
        ];
    }
}
