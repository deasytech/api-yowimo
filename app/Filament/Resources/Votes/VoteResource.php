<?php

namespace App\Filament\Resources\Votes;

use App\Filament\Resources\Votes\Pages\ListVotes;
use App\Filament\Resources\Votes\Pages\ViewVote;
use App\Filament\Resources\Votes\Schemas\VoteInfolist;
use App\Filament\Resources\Votes\Tables\VotesTable;
use App\Models\Vote;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class VoteResource extends Resource
{
    protected static ?string $model = Vote::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-check-badge';

    protected static string|UnitEnum|null $navigationGroup = 'Gameplay';

    protected static ?int $navigationSort = 4;

    public static function infolist(Schema $schema): Schema
    {
        return VoteInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return VotesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    /**
     * The vote ledger is append-only (see Vote::booted()) — this panel is
     * read-only/audit access, never a write path.
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
            'index' => ListVotes::route('/'),
            'view' => ViewVote::route('/{record}'),
        ];
    }
}
