<?php

namespace App\Filament\Resources\PackCards\Pages;

use App\Filament\Resources\PackCards\PackCardResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewPackCard extends ViewRecord
{
    protected static string $resource = PackCardResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
