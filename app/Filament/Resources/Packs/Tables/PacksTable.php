<?php

namespace App\Filament\Resources\Packs\Tables;

use App\Enums\PackCategory;
use App\Filament\Support\BadgeColors;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
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
                ImageColumn::make('cover_image_url')
                    ->label('')
                    ->circular(),
                TextColumn::make('sort_order')
                    ->sortable(),
                TextColumn::make('emoji'),
                TextColumn::make('name')
                    ->searchable()
                    ->weight(FontWeight::SemiBold)
                    ->description(fn ($record) => $record->tag),
                TextColumn::make('gameType.name')
                    ->label('Game type')
                    ->sortable(),
                TextColumn::make('category')
                    ->badge()
                    ->color(fn (PackCategory $state) => BadgeColors::packCategory($state)),
                TextColumn::make('price')
                    ->numeric()
                    ->suffix(' tokens')
                    ->sortable(),
                TextColumn::make('cards_count')
                    ->numeric(),
                IconColumn::make('is_featured')
                    ->boolean(),
                IconColumn::make('is_active')
                    ->boolean(),
            ])
            ->defaultSort('sort_order')
            ->striped()
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
