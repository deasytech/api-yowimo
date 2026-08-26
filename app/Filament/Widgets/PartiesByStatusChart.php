<?php

namespace App\Filament\Widgets;

use App\Enums\PartyStatus;
use App\Models\Party;
use Filament\Widgets\ChartWidget;

class PartiesByStatusChart extends ChartWidget
{
    protected ?string $heading = 'Parties by Status';

    protected static bool $isLazy = false;

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getData(): array
    {
        $counts = Party::selectRaw('status, count(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        $statuses = collect(PartyStatus::cases());

        return [
            'datasets' => [
                [
                    'label' => 'Parties',
                    'data' => $statuses->map(fn (PartyStatus $status) => $counts->get($status->value, 0))->values(),
                ],
            ],
            'labels' => $statuses->map(fn (PartyStatus $status) => ucfirst(str_replace('_', ' ', $status->value)))->values(),
        ];
    }
}
