<?php

namespace App\Filament\Resources\PackCards\Pages;

use App\Filament\Resources\PackCards\PackCardResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditPackCard extends EditRecord
{
    protected static string $resource = PackCardResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
