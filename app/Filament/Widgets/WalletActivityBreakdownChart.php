<?php

namespace App\Filament\Widgets;

use App\Enums\WalletTransactionType;
use App\Models\WalletTransaction;
use Filament\Widgets\ChartWidget;

class WalletActivityBreakdownChart extends ChartWidget
{
    protected ?string $heading = 'Wallet Activity Breakdown';

    protected static bool $isLazy = false;

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getData(): array
    {
        $counts = WalletTransaction::selectRaw('type, count(*) as aggregate')
            ->groupBy('type')
            ->pluck('aggregate', 'type');

        $types = collect(WalletTransactionType::cases());

        return [
            'datasets' => [
                [
                    'label' => 'Transactions',
                    'data' => $types->map(fn (WalletTransactionType $type) => $counts->get($type->value, 0))->values(),
                    'backgroundColor' => ['#f59e0b', '#3b82f6', '#ef4444', '#10b981', '#8b5cf6'],
                ],
            ],
            'labels' => $types->map(fn (WalletTransactionType $type) => ucfirst(str_replace('_', ' ', $type->value)))->values(),
        ];
    }
}
