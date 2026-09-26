<?php

namespace App\Filament\Resources\XpTransactions;

use App\Filament\Resources\XpTransactions\Pages\ListXpTransactions;
use App\Filament\Resources\XpTransactions\Pages\ViewXpTransaction;
use App\Filament\Resources\XpTransactions\Schemas\XpTransactionInfolist;
use App\Filament\Resources\XpTransactions\Tables\XpTransactionsTable;
use App\Models\XpTransaction;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class XpTransactionResource extends Resource
{
    protected static ?string $model = XpTransaction::class;

    protected static ?string $navigationLabel = 'XP Transactions';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-bolt';

    protected static string|UnitEnum|null $navigationGroup = 'Progression';

    protected static ?int $navigationSort = 3;

    public static function infolist(Schema $schema): Schema
    {
        return XpTransactionInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return XpTransactionsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    /**
     * The XP ledger is append-only (see XpTransaction::booted()) — this
     * panel is read-only/audit access, never a write path.
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
            'index' => ListXpTransactions::route('/'),
            'view' => ViewXpTransaction::route('/{record}'),
        ];
    }
}
