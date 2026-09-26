<?php

namespace App\Filament\Resources\XpTransactions\Pages;

use App\Filament\Resources\XpTransactions\XpTransactionResource;
use Filament\Resources\Pages\ListRecords;

class ListXpTransactions extends ListRecords
{
    protected static string $resource = XpTransactionResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
