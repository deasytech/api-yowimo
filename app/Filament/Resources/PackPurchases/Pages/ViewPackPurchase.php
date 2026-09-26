<?php

namespace App\Filament\Resources\PackPurchases\Pages;

use App\Filament\Resources\PackPurchases\PackPurchaseResource;
use Filament\Resources\Pages\ViewRecord;

class ViewPackPurchase extends ViewRecord
{
    protected static string $resource = PackPurchaseResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
