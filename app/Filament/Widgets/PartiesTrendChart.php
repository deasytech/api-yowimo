<?php

namespace App\Filament\Widgets;

use App\Enums\PartyStatus;
use App\Models\Party;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;

class PartiesTrendChart extends ChartWidget
{
    protected ?string $heading = 'Parties Trend (Last 30 Days)';

    protected static bool $isLazy = false;

    protected function getType(): string
    {
        return 'line';
    }

    protected function getData(): array
    {
        $start = now()->subDays(29)->startOfDay();
        $days = collect(range(0, 29))->map(fn (int $offset) => $start->copy()->addDays($offset)->toDateString());

        $created = Party::where('created_at', '>=', $start)
            ->pluck('created_at')
            ->countBy(fn (Carbon $date) => $date->toDateString());

        $ended = Party::where('status', PartyStatus::Ended)
            ->where('updated_at', '>=', $start)
            ->pluck('updated_at')
            ->countBy(fn (Carbon $date) => $date->toDateString());

        return [
            'datasets' => [
                [
                    'label' => 'Created',
                    'data' => $days->map(fn (string $day) => $created->get($day, 0))->values(),
                    'fill' => true,
                ],
                [
                    'label' => 'Ended',
                    'data' => $days->map(fn (string $day) => $ended->get($day, 0))->values(),
                    'fill' => false,
                ],
            ],
            'labels' => $days->map(fn (string $day) => Carbon::parse($day)->format('M j'))->values(),
        ];
    }
}
