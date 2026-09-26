<?php

namespace App\Filament\Resources\Votes\Schemas;

use App\Enums\VoteCategory;
use App\Filament\Support\BadgeColors;
use App\Filament\Support\InfolistSections;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class VoteInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Vote')
                    ->icon(Heroicon::OutlinedCheckBadge)
                    ->columns(2)
                    ->schema([
                        TextEntry::make('id'),
                        TextEntry::make('turn_id')
                            ->label('Turn'),
                        TextEntry::make('voter.username')
                            ->label('Voter'),
                        TextEntry::make('category')
                            ->badge()
                            ->color(fn (VoteCategory $state) => BadgeColors::voteCategory($state)),
                    ]),

                InfolistSections::timestamps(),
            ]);
    }
}
