<?php

namespace App\Filament\Widgets;

use App\Enums\WalletTransactionType;
use App\Models\WalletTransaction;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;

class TokensPurchasedTrendChart extends ChartWidget
{
    protected ?string $heading = 'Tokens Purchased Trend (Last 30 Days)';

    protected string $color = 'warning';

    protected static bool $isLazy = false;

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getData(): array
    {
        $start = now()->subDays(29)->startOfDay();
        $days = collect(range(0, 29))->map(fn (int $offset) => $start->copy()->addDays($offset)->toDateString());

        $totals = WalletTransaction::where('type', WalletTransactionType::TopUp)
            ->where('created_at', '>=', $start)
            ->get(['created_at', 'amount'])
            ->groupBy(fn (WalletTransaction $transaction) => $transaction->created_at->toDateString())
            ->map(fn ($group) => (int) $group->sum('amount'));

        return [
            'datasets' => [
                [
                    'label' => 'Tokens purchased',
                    'data' => $days->map(fn (string $day) => $totals->get($day, 0))->values(),
                ],
            ],
            'labels' => $days->map(fn (string $day) => Carbon::parse($day)->format('M j'))->values(),
        ];
    }
}
