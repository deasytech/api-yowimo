<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Enums\UserStatus;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('username')
                    ->maxLength(255)
                    ->unique(ignoreRecord: true),
                TextInput::make('email')
                    ->email()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true),
                TextInput::make('first_name')
                    ->maxLength(255),
                TextInput::make('last_name')
                    ->maxLength(255),
                TextInput::make('display_name')
                    ->maxLength(255),
                Textarea::make('bio')
                    ->columnSpanFull(),
                TextInput::make('country_code')
                    ->maxLength(2),
                Select::make('status')
                    ->options([
                        UserStatus::Active->value => 'Active',
                        UserStatus::Deactivated->value => 'Deactivated',
                    ])
                    ->required(),
                Toggle::make('is_admin')
                    ->label('Admin access')
                    ->helperText('Grants access to this admin panel.'),
            ]);
    }
}
