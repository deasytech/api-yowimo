<?php

namespace App\Filament\Widgets;

use App\Enums\PartyStatus;
use App\Enums\WalletTransactionType;
use App\Models\Party;
use App\Models\User;
use App\Models\WalletTransaction;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class OverviewStats extends StatsOverviewWidget
{
    protected static bool $isLazy = false;

    protected function getStats(): array
    {
        return [
            $this->totalUsersStat(),
            $this->totalPartiesStat(),
            $this->tokensPurchasedStat(),
            $this->partyCompletionRateStat(),
        ];
    }

    private function totalUsersStat(): Stat
    {
        $newThisWeek = User::where('created_at', '>=', now()->subDays(7))->count();

        return Stat::make('Total Users', number_format(User::count()))
            ->description("{$newThisWeek} new this week")
            ->descriptionIcon('heroicon-m-user-plus')
            ->chart($this->dailyCounts(User::class))
            ->color('primary');
    }

    private function totalPartiesStat(): Stat
    {
        $live = Party::where('status', PartyStatus::Live)->count();

        return Stat::make('Total Parties', number_format(Party::count()))
            ->description("{$live} live right now")
            ->descriptionIcon('heroicon-m-bolt')
            ->chart($this->dailyCounts(Party::class))
            ->color('success');
    }

    private function tokensPurchasedStat(): Stat
    {
        $purchased = (int) WalletTransaction::where('type', WalletTransactionType::TopUp)->sum('amount');
        $spent = (int) abs(WalletTransaction::where('type', WalletTransactionType::Purchase)->sum('amount'));

        return Stat::make('Tokens Purchased', number_format($purchased))
            ->description(number_format($spent).' tokens spent on packs')
            ->descriptionIcon('heroicon-m-shopping-bag')
            ->chart($this->dailyTopUpTotals())
            ->color('warning');
    }

    private function partyCompletionRateStat(): Stat
    {
        $counted = Party::whereIn('status', [
            PartyStatus::Scheduled,
            PartyStatus::Live,
            PartyStatus::Ended,
            PartyStatus::Cancelled,
        ])->count();

        $ended = Party::where('status', PartyStatus::Ended)->count();
        $cancelled = Party::where('status', PartyStatus::Cancelled)->count();

        $rate = $counted > 0 ? round(($ended / $counted) * 100, 1) : 0;

        return Stat::make('Party Completion Rate', "{$rate}%")
            ->description("{$cancelled} cancelled")
            ->descriptionIcon('heroicon-m-x-circle')
            ->color($cancelled > 0 ? 'danger' : 'success');
    }

    /**
     * Daily row counts for the last 14 days, oldest first — used as sparkline data.
     *
     * @param  class-string  $model
     * @return array<int>
     */
    private function dailyCounts(string $model): array
    {
        $start = now()->subDays(13)->startOfDay();

        $rows = $model::where('created_at', '>=', $start)
            ->pluck('created_at')
            ->map(fn (Carbon $date) => $date->toDateString());

        return $this->bucketByDay($rows, $start);
    }

    /**
     * @return array<int>
     */
    private function dailyTopUpTotals(): array
    {
        $start = now()->subDays(13)->startOfDay();

        $rows = WalletTransaction::where('type', WalletTransactionType::TopUp)
            ->where('created_at', '>=', $start)
            ->get(['created_at', 'amount']);

        $byDay = $rows->groupBy(fn (WalletTransaction $transaction) => $transaction->created_at->toDateString())
            ->map(fn ($group) => (int) $group->sum('amount'));

        $days = collect(range(0, 13))->map(fn (int $offset) => $start->copy()->addDays($offset)->toDateString());

        return $days->map(fn (string $day) => $byDay->get($day, 0))->values()->all();
    }

    /**
     * @param  Collection<int, string>  $dates
     * @return array<int>
     */
    private function bucketByDay($dates, Carbon $start): array
    {
        $counts = $dates->countBy();

        $days = collect(range(0, 13))->map(fn (int $offset) => $start->copy()->addDays($offset)->toDateString());

        return $days->map(fn (string $day) => $counts->get($day, 0))->values()->all();
    }
}
