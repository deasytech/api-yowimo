<?php

namespace App\Filament\Resources\Packs\Pages;

use App\Filament\Resources\Packs\PackResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewPack extends ViewRecord
{
    protected static string $resource = PackResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
