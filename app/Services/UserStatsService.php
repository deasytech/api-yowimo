<?php

namespace App\Services;

use App\Enums\PartyStatus;
use App\Enums\XpTransactionType;
use App\Models\PartyMember;
use App\Models\User;
use App\Models\XpTransaction;

class UserStatsService
{
    /**
     * Profile screen stat tiles. A membership row exists from the moment a
     * party is created — even a draft the host never launches — so this
     * counts by the party's own status rather than membership status, which
     * is what keeps a draft or cancelled party from inflating the number.
     * MVP has no running counter anywhere; it's tallied fresh from the
     * append-only xp_transactions ledger each time.
     *
     * @return array{parties_count: int, mvp_count: int}
     */
    public function forUser(User $user): array
    {
        return [
            'parties_count' => PartyMember::query()
                ->where('user_id', $user->id)
                ->whereHas('party', fn ($query) => $query->where('status', PartyStatus::Ended))
                ->count(),
            'mvp_count' => XpTransaction::query()
                ->where('user_id', $user->id)
                ->where('type', XpTransactionType::MvpBonus)
                ->count(),
        ];
    }
}
