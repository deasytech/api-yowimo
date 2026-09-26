<?php

namespace App\Filament\Resources\PartyLikes\Pages;

use App\Filament\Resources\PartyLikes\PartyLikeResource;
use Filament\Resources\Pages\ViewRecord;

class ViewPartyLike extends ViewRecord
{
    protected static string $resource = PartyLikeResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
