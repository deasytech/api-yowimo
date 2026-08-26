<?php

namespace App\Filament\Resources\TokenBundles\Pages;

use App\Filament\Resources\TokenBundles\TokenBundleResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditTokenBundle extends EditRecord
{
    protected static string $resource = TokenBundleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
