<?php

namespace App\Filament\Resources\PartyLikes\Pages;

use App\Filament\Resources\PartyLikes\PartyLikeResource;
use Filament\Resources\Pages\ListRecords;

class ListPartyLikes extends ListRecords
{
    protected static string $resource = PartyLikeResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
