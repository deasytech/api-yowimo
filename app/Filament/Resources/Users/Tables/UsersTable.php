<?php

namespace App\Filament\Resources\Users\Tables;

use App\Enums\UserStatus;
use App\Filament\Support\BadgeColors;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('avatar_url')
                    ->label('')
                    ->circular(),
                TextColumn::make('id')
                    ->sortable(),
                TextColumn::make('username')
                    ->searchable()
                    ->weight(FontWeight::SemiBold),
                TextColumn::make('email')
                    ->searchable()
                    ->copyable(),
                TextColumn::make('display_name')
                    ->searchable(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (UserStatus $state) => BadgeColors::userStatus($state)),
                IconColumn::make('is_admin')
                    ->boolean()
                    ->label('Admin'),
                TextColumn::make('last_seen_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->striped()
            ->filters([
                TrashedFilter::make(),
                TernaryFilter::make('is_admin')
                    ->label('Admin'),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([]);
    }
}
