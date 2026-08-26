<?php

namespace App\Filament\Resources\GameTypes\Pages;

use App\Filament\Resources\GameTypes\GameTypeResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditGameType extends EditRecord
{
    protected static string $resource = GameTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
