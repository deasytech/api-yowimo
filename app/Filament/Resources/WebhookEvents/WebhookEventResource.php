<?php

namespace App\Filament\Resources\WebhookEvents;

use App\Filament\Resources\WebhookEvents\Pages\ListWebhookEvents;
use App\Filament\Resources\WebhookEvents\Pages\ViewWebhookEvent;
use App\Filament\Resources\WebhookEvents\Schemas\WebhookEventInfolist;
use App\Filament\Resources\WebhookEvents\Tables\WebhookEventsTable;
use App\Models\WebhookEvent;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class WebhookEventResource extends Resource
{
    protected static ?string $model = WebhookEvent::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-server';

    protected static string|UnitEnum|null $navigationGroup = 'System';

    protected static ?int $navigationSort = 4;

    public static function infolist(Schema $schema): Schema
    {
        return WebhookEventInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return WebhookEventsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    /**
     * Inbound provider webhooks (Clerk, Paystack) are recorded once for
     * replay/debugging — never authored or rewritten by the panel.
     */
    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListWebhookEvents::route('/'),
            'view' => ViewWebhookEvent::route('/{record}'),
        ];
    }
}
