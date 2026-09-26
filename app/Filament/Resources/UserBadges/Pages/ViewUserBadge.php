<?php

namespace App\Filament\Resources\UserBadges\Pages;

use App\Filament\Resources\UserBadges\UserBadgeResource;
use Filament\Resources\Pages\ViewRecord;

class ViewUserBadge extends ViewRecord
{
    protected static string $resource = UserBadgeResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
