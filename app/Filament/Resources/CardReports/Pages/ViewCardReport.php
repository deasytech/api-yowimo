<?php

namespace App\Filament\Resources\CardReports\Pages;

use App\Filament\Resources\CardReports\CardReportResource;
use Filament\Resources\Pages\ViewRecord;

class ViewCardReport extends ViewRecord
{
    protected static string $resource = CardReportResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
