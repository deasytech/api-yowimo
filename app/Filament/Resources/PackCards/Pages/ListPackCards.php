<?php

namespace App\Filament\Resources\PackCards\Pages;

use App\Filament\Resources\PackCards\PackCardResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPackCards extends ListRecords
{
    protected static string $resource = PackCardResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
