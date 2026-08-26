<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class UserInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('id'),
                TextEntry::make('username'),
                TextEntry::make('email'),
                TextEntry::make('display_name'),
                TextEntry::make('first_name'),
                TextEntry::make('last_name'),
                TextEntry::make('bio')
                    ->columnSpanFull(),
                TextEntry::make('country_code'),
                TextEntry::make('status')
                    ->badge(),
                IconEntry::make('is_admin')
                    ->boolean()
                    ->label('Admin'),
                TextEntry::make('last_seen_at')
                    ->dateTime(),
                TextEntry::make('created_at')
                    ->dateTime(),
                TextEntry::make('updated_at')
                    ->dateTime(),
            ]);
    }
}
