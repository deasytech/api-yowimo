<?php

namespace App\Filament\Resources\PackCards;

use App\Filament\Resources\PackCards\Pages\CreatePackCard;
use App\Filament\Resources\PackCards\Pages\EditPackCard;
use App\Filament\Resources\PackCards\Pages\ListPackCards;
use App\Filament\Resources\PackCards\Pages\ViewPackCard;
use App\Filament\Resources\PackCards\Schemas\PackCardForm;
use App\Filament\Resources\PackCards\Schemas\PackCardInfolist;
use App\Filament\Resources\PackCards\Tables\PackCardsTable;
use App\Models\PackCard;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use UnitEnum;

class PackCardResource extends Resource
{
    protected static ?string $model = PackCard::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static string|UnitEnum|null $navigationGroup = 'Catalog';

    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return PackCardForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return PackCardInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PackCardsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPackCards::route('/'),
            'create' => CreatePackCard::route('/create'),
            'view' => ViewPackCard::route('/{record}'),
            'edit' => EditPackCard::route('/{record}/edit'),
        ];
    }
}
