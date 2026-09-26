<?php

namespace App\Filament\Resources\WebhookEvents\Schemas;

use Filament\Infolists\Components\KeyValueEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class WebhookEventInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Webhook')
                    ->icon(Heroicon::OutlinedServer)
                    ->columns(2)
                    ->schema([
                        TextEntry::make('id'),
                        TextEntry::make('provider'),
                        TextEntry::make('event_type'),
                        TextEntry::make('event_id')
                            ->label('Event ID')
                            ->copyable(),
                        TextEntry::make('processed_at')
                            ->label('Processed')
                            ->dateTime()
                            ->placeholder('Not processed yet'),
                        KeyValueEntry::make('payload')
                            ->label('Payload')
                            ->columnSpanFull(),
                    ]),

                Section::make('Timestamps')
                    ->icon(Heroicon::OutlinedClock)
                    ->schema([
                        TextEntry::make('created_at')
                            ->dateTime(),
                        TextEntry::make('updated_at')
                            ->dateTime(),
                    ]),
            ]);
    }
}
