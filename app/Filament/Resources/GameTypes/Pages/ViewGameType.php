<?php

namespace App\Filament\Resources\GameTypes\Pages;

use App\Filament\Resources\GameTypes\GameTypeResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewGameType extends ViewRecord
{
    protected static string $resource = GameTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
