<?php

namespace App\Filament\Resources\TokenBundles;

use App\Filament\Resources\TokenBundles\Pages\CreateTokenBundle;
use App\Filament\Resources\TokenBundles\Pages\EditTokenBundle;
use App\Filament\Resources\TokenBundles\Pages\ListTokenBundles;
use App\Filament\Resources\TokenBundles\Pages\ViewTokenBundle;
use App\Filament\Resources\TokenBundles\Schemas\TokenBundleForm;
use App\Filament\Resources\TokenBundles\Schemas\TokenBundleInfolist;
use App\Filament\Resources\TokenBundles\Tables\TokenBundlesTable;
use App\Models\TokenBundle;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class TokenBundleResource extends Resource
{
    protected static ?string $model = TokenBundle::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return TokenBundleForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return TokenBundleInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TokenBundlesTable::configure($table);
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
            'index' => ListTokenBundles::route('/'),
            'create' => CreateTokenBundle::route('/create'),
            'view' => ViewTokenBundle::route('/{record}'),
            'edit' => EditTokenBundle::route('/{record}/edit'),
        ];
    }
}
