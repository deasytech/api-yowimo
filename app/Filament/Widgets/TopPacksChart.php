<?php

namespace App\Filament\Widgets;

use App\Models\Party;
use Filament\Support\RawJs;
use Filament\Widgets\ChartWidget;

class TopPacksChart extends ChartWidget
{
    protected ?string $heading = 'Top Packs by Parties Hosted';

    protected static bool $isLazy = false;

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getData(): array
    {
        $rows = Party::query()
            ->join('packs', 'packs.id', '=', 'parties.pack_id')
            ->whereNotNull('parties.pack_id')
            ->selectRaw('packs.name as pack_name, count(*) as aggregate')
            ->groupBy('packs.id', 'packs.name')
            ->orderByDesc('aggregate')
            ->limit(5)
            ->get();

        return [
            'datasets' => [
                [
                    'label' => 'Parties',
                    'data' => $rows->pluck('aggregate'),
                ],
            ],
            'labels' => $rows->pluck('pack_name'),
        ];
    }

    protected function getOptions(): array|RawJs|null
    {
        return RawJs::make(<<<'JS'
            {
                indexAxis: 'y',
                scales: { y: { beginAtZero: true } },
            }
        JS);
    }
}
