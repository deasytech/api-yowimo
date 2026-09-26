<?php

namespace App\Filament\Resources\Badges\Schemas;

use App\Enums\BadgeKey;
use App\Filament\Support\BadgeColors;
use App\Filament\Support\InfolistSections;
use App\Models\Badge;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class BadgeInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Badge')
                    ->icon(Heroicon::OutlinedAcademicCap)
                    ->columns(2)
                    ->schema([
                        TextEntry::make('id'),
                        TextEntry::make('key')
                            ->badge()
                            ->color(fn (BadgeKey $state) => BadgeColors::badgeKey($state)),
                        TextEntry::make('name'),
                        TextEntry::make('icon')
                            ->placeholder('—'),
                        TextEntry::make('description')
                            ->columnSpanFull(),
                        TextEntry::make('times_earned')
                            ->label('Times earned')
                            ->state(fn (Badge $record): int => $record->userBadges()->count())
                            ->numeric(),
                    ]),

                InfolistSections::timestamps(),
            ]);
    }
}
