<?php

namespace App\Filament\Resources\Packs\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class PacksTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('sort_order')
                    ->sortable(),
                TextColumn::make('emoji'),
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('gameType.name')
                    ->label('Game type'),
                TextColumn::make('category')
                    ->badge(),
                TextColumn::make('price'),
                TextColumn::make('cards_count'),
                IconColumn::make('is_featured')
                    ->boolean(),
                IconColumn::make('is_active')
                    ->boolean(),
            ])
            ->defaultSort('sort_order')
            ->filters([
                SelectFilter::make('category')
                    ->options([
                        'spicy' => 'Spicy',
                        'couples' => 'Couples',
                        'family' => 'Family',
                        'corporate' => 'Corporate',
                        'limited' => 'Limited',
                    ]),
                TernaryFilter::make('is_active'),
                TernaryFilter::make('is_featured'),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
