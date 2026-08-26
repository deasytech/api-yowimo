<?php

namespace App\Filament\Resources\TokenBundles\Pages;

use App\Filament\Resources\TokenBundles\TokenBundleResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListTokenBundles extends ListRecords
{
    protected static string $resource = TokenBundleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
