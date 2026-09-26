<?php

namespace App\Filament\Resources\PartyMembers\Schemas;

use App\Enums\PartyMemberStatus;
use App\Filament\Support\BadgeColors;
use App\Filament\Support\InfolistSections;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class PartyMemberInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Membership')
                    ->icon(Heroicon::OutlinedUserGroup)
                    ->columns(2)
                    ->schema([
                        TextEntry::make('id'),
                        TextEntry::make('party.title')
                            ->label('Party'),
                        TextEntry::make('user.username')
                            ->label('Member'),
                        TextEntry::make('status')
                            ->badge()
                            ->color(fn (PartyMemberStatus $state) => BadgeColors::partyMemberStatus($state)),
                        TextEntry::make('joined_at')
                            ->dateTime(),
                        TextEntry::make('left_at')
                            ->dateTime()
                            ->placeholder('Still a member'),
                    ]),

                InfolistSections::timestamps(),
            ]);
    }
}
