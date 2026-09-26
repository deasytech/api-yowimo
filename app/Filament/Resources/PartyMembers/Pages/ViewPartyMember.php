<?php

namespace App\Filament\Resources\PartyMembers\Pages;

use App\Filament\Resources\PartyMembers\PartyMemberResource;
use Filament\Resources\Pages\ViewRecord;

class ViewPartyMember extends ViewRecord
{
    protected static string $resource = PartyMemberResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
