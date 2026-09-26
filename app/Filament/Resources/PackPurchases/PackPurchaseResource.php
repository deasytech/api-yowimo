<?php

namespace App\Filament\Resources\PackPurchases;

use App\Filament\Resources\PackPurchases\Pages\ListPackPurchases;
use App\Filament\Resources\PackPurchases\Pages\ViewPackPurchase;
use App\Filament\Resources\PackPurchases\Schemas\PackPurchaseInfolist;
use App\Filament\Resources\PackPurchases\Tables\PackPurchasesTable;
use App\Models\PackPurchase;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class PackPurchaseResource extends Resource
{
    protected static ?string $model = PackPurchase::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-shopping-cart';

    protected static string|UnitEnum|null $navigationGroup = 'Finance';

    protected static ?int $navigationSort = 4;

    public static function infolist(Schema $schema): Schema
    {
        return PackPurchaseInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PackPurchasesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    /**
     * Purchases flow through PurchaseService and the wallet ledger — the
     * panel only audits them, never mints or reverses them.
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
            'index' => ListPackPurchases::route('/'),
            'view' => ViewPackPurchase::route('/{record}'),
        ];
    }
}
