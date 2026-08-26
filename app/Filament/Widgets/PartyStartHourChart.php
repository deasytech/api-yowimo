<?php

namespace App\Filament\Widgets;

use App\Models\Party;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;

class PartyStartHourChart extends ChartWidget
{
    protected ?string $heading = 'Party Start Times by Hour';

    protected static bool $isLazy = false;

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getData(): array
    {
        $timestamps = Party::query()
            ->selectRaw('coalesce(starts_at, created_at) as at')
            ->get()
            ->pluck('at');

        $byHour = $timestamps
            ->map(fn (?string $at) => $at ? Carbon::parse($at)->hour : null)
            ->filter(fn (?int $hour) => $hour !== null)
            ->countBy();

        $hours = collect(range(0, 23));

        return [
            'datasets' => [
                [
                    'label' => 'Parties',
                    'data' => $hours->map(fn (int $hour) => $byHour->get($hour, 0))->values(),
                ],
            ],
            'labels' => $hours->map(fn (int $hour) => sprintf('%02d:00', $hour))->values(),
        ];
    }
}
