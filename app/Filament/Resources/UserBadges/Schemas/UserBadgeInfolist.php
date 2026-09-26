<?php

namespace App\Filament\Resources\UserBadges\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class UserBadgeInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Earned Badge')
                    ->icon(Heroicon::OutlinedStar)
                    ->columns(2)
                    ->schema([
                        TextEntry::make('id'),
                        TextEntry::make('user.username')
                            ->label('User'),
                        TextEntry::make('badge.name')
                            ->label('Badge'),
                        TextEntry::make('badge.key')
                            ->label('Key'),
                        TextEntry::make('earned_at')
                            ->dateTime(),
                        TextEntry::make('reference_type')
                            ->label('Reference')
                            ->state(fn ($record): ?string => $record->reference_type
                                ? "{$record->reference_type} #{$record->reference_id}"
                                : null)
                            ->placeholder('None'),
                    ]),
            ]);
    }
}
