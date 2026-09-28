<?php

namespace App\Filament\Resources\CardReports\Pages;

use App\Filament\Resources\CardReports\CardReportResource;
use Filament\Resources\Pages\ListRecords;

class ListCardReports extends ListRecords
{
    protected static string $resource = CardReportResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
