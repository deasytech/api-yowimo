<?php

namespace App\Filament\Resources\PushTokens\Schemas;

use App\Enums\PushPlatform;
use App\Filament\Support\BadgeColors;
use App\Filament\Support\InfolistSections;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class PushTokenInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Device Token')
                    ->icon(Heroicon::OutlinedDevicePhoneMobile)
                    ->columns(2)
                    ->schema([
                        TextEntry::make('id'),
                        TextEntry::make('user.username')
                            ->label('User'),
                        TextEntry::make('platform')
                            ->badge()
                            ->color(fn (PushPlatform $state) => BadgeColors::pushPlatform($state)),
                        TextEntry::make('token')
                            ->label('Token')
                            ->copyable()
                            ->columnSpanFull(),
                    ]),

                InfolistSections::timestamps(),
            ]);
    }
}
