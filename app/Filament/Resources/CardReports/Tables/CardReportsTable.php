<?php

namespace App\Filament\Resources\CardReports\Tables;

use App\Enums\CardReportReason;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class CardReportsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->sortable(),
                TextColumn::make('packCard.text')
                    ->label('Card')
                    ->limit(50)
                    ->tooltip(fn ($state) => $state),
                TextColumn::make('reporter.username')
                    ->label('Reporter')
                    ->searchable(),
                TextColumn::make('reason')
                    ->badge(),
                TextColumn::make('note')
                    ->limit(50)
                    ->tooltip(fn ($state) => $state)
                    ->placeholder('—'),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->striped()
            ->filters([
                SelectFilter::make('reason')
                    ->options(fn () => collect(CardReportReason::cases())->mapWithKeys(fn ($case) => [$case->value => ucfirst($case->value)])),
            ])
            ->recordActions([
                ViewAction::make(),
            ])
            ->toolbarActions([]);
    }
}
