<?php

namespace App\Filament\Resources\PartyMembers\Pages;

use App\Filament\Resources\PartyMembers\PartyMemberResource;
use Filament\Resources\Pages\ListRecords;

class ListPartyMembers extends ListRecords
{
    protected static string $resource = PartyMemberResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
