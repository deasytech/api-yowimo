<?php

namespace App\Filament\Resources\Turns\Pages;

use App\Filament\Resources\Turns\TurnResource;
use Filament\Resources\Pages\ViewRecord;

class ViewTurn extends ViewRecord
{
    protected static string $resource = TurnResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
