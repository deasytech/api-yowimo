<?php

namespace App\Filament\Resources\PackCards\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class PackCardsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('pack.name')
                    ->label('Pack')
                    ->searchable(),
                TextColumn::make('kind')
                    ->badge(),
                TextColumn::make('text')
                    ->limit(60)
                    ->searchable(),
                TextColumn::make('position')
                    ->sortable(),
                IconColumn::make('is_preview')
                    ->boolean(),
            ])
            ->defaultSort('position')
            ->filters([
                SelectFilter::make('kind')
                    ->options([
                        'truth' => 'Truth',
                        'dare' => 'Dare',
                    ]),
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
