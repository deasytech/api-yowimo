<?php

namespace App\Filament\Resources\PackPurchases\Pages;

use App\Filament\Resources\PackPurchases\PackPurchaseResource;
use Filament\Resources\Pages\ListRecords;

class ListPackPurchases extends ListRecords
{
    protected static string $resource = PackPurchaseResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
