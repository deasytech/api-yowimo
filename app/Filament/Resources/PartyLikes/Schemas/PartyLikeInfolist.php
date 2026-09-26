<?php

namespace App\Filament\Resources\PartyLikes\Schemas;

use App\Filament\Support\InfolistSections;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class PartyLikeInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Like')
                    ->icon(Heroicon::OutlinedHandThumbUp)
                    ->columns(2)
                    ->schema([
                        TextEntry::make('id'),
                        TextEntry::make('party.title')
                            ->label('Party'),
                        TextEntry::make('user.username')
                            ->label('Liked by'),
                    ]),

                InfolistSections::timestamps(),
            ]);
    }
}
