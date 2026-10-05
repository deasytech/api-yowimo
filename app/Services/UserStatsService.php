<?php

namespace App\Services;

use App\Enums\FriendshipStatus;
use App\Enums\PartyStatus;
use App\Enums\XpTransactionType;
use App\Models\Friendship;
use App\Models\Party;
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

    /**
     * Stats shown on another user's public profile — a different cut from
     * forUser()'s own-profile tiles, not just that same shape reused:
     * parties are split into joined vs. created rather than one combined
     * count, and parties_created_count counts every status (a host should
     * see their own draft/scheduled parties here), unlike parties_count's
     * ended-only "parties actually played" meaning.
     *
     * @return array{friends_count: int, parties_joined_count: int, parties_created_count: int}
     */
    public function publicStatsFor(User $user): array
    {
        return [
            'friends_count' => Friendship::query()
                ->where('status', FriendshipStatus::Accepted)
                ->where(fn ($query) => $query->where('sender_id', $user->id)->orWhere('receiver_id', $user->id))
                ->count(),
            'parties_joined_count' => PartyMember::query()
                ->where('user_id', $user->id)
                ->whereHas('party', fn ($query) => $query->where('host_id', '!=', $user->id)->where('status', PartyStatus::Ended))
                ->count(),
            'parties_created_count' => Party::query()->where('host_id', $user->id)->count(),
        ];
    }
}
