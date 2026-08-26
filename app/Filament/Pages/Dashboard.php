<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\OverviewStats;
use App\Filament\Widgets\PartiesByStatusChart;
use App\Filament\Widgets\PartiesTrendChart;
use App\Filament\Widgets\PartyStartHourChart;
use App\Filament\Widgets\TokensPurchasedTrendChart;
use App\Filament\Widgets\TopPacksChart;
use App\Filament\Widgets\WalletActivityBreakdownChart;
use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    public function getWidgets(): array
    {
        return [
            OverviewStats::class,
            PartiesTrendChart::class,
            TokensPurchasedTrendChart::class,
            PartiesByStatusChart::class,
            WalletActivityBreakdownChart::class,
            TopPacksChart::class,
            PartyStartHourChart::class,
        ];
    }

    public function getColumns(): int|array
    {
        return 3;
    }
}
