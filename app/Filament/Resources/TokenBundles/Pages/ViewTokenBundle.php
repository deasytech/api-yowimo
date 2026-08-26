<?php

namespace App\Filament\Resources\TokenBundles\Pages;

use App\Filament\Resources\TokenBundles\TokenBundleResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewTokenBundle extends ViewRecord
{
    protected static string $resource = TokenBundleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
