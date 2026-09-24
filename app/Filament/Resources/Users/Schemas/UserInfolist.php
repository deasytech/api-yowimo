<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Enums\UserStatus;
use App\Filament\Support\BadgeColors;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class UserInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Identity')
                    ->icon(Heroicon::OutlinedIdentification)
                    ->columns(2)
                    ->schema([
                        ImageEntry::make('avatar_url')
                            ->label('Avatar')
                            ->circular()
                            ->columnSpanFull(),
                        TextEntry::make('id'),
                        TextEntry::make('username'),
                        TextEntry::make('email')
                            ->copyable(),
                        TextEntry::make('display_name'),
                        TextEntry::make('first_name'),
                        TextEntry::make('last_name'),
                    ]),

                Section::make('Profile')
                    ->icon(Heroicon::OutlinedUsers)
                    ->columns(2)
                    ->schema([
                        TextEntry::make('bio')
                            ->columnSpanFull(),
                        TextEntry::make('country_code'),
                    ]),

                Section::make('Account')
                    ->description('Status and admin panel access.')
                    ->icon(Heroicon::OutlinedShieldCheck)
                    ->columns(2)
                    ->schema([
                        TextEntry::make('status')
                            ->badge()
                            ->color(fn (UserStatus $state) => BadgeColors::userStatus($state)),
                        IconEntry::make('is_admin')
                            ->boolean()
                            ->label('Admin'),
                    ]),

                Section::make('Timestamps')
                    ->icon(Heroicon::OutlinedClock)
                    ->columns(3)
                    ->collapsible()
                    ->schema([
                        TextEntry::make('last_seen_at')
                            ->dateTime(),
                        TextEntry::make('created_at')
                            ->dateTime(),
                        TextEntry::make('updated_at')
                            ->dateTime(),
                    ]),
            ]);
    }
}
