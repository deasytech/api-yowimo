<?php

namespace App\Filament\Resources\Badges\Tables;

use App\Enums\BadgeKey;
use App\Filament\Support\BadgeColors;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class BadgesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('key')
                    ->badge()
                    ->color(fn (BadgeKey $state) => BadgeColors::badgeKey($state)),
                TextColumn::make('name')
                    ->searchable()
                    ->weight(FontWeight::SemiBold),
                TextColumn::make('description')
                    ->limit(50)
                    ->tooltip(fn (string $state): string => $state),
                TextColumn::make('icon')
                    ->limit(20)
                    ->placeholder('—'),
                TextColumn::make('user_badges_count')
                    ->label('Earned')
                    ->counts('userBadges')
                    ->numeric(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('id')
            ->striped()
            ->filters([])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([]);
    }
}
