<?php

namespace App\Services\Parties;

use App\Models\Party;
use App\Models\PartyLike;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class PartyLikeService
{
    public function like(User $user, Party $party): Party
    {
        DB::transaction(function () use ($user, $party) {
            $like = PartyLike::query()->firstOrCreate([
                'party_id' => $party->id,
                'user_id' => $user->id,
            ]);

            if ($like->wasRecentlyCreated) {
                $party->increment('likes_count');
            }
        });

        return $party->refresh();
    }

    public function unlike(User $user, Party $party): Party
    {
        DB::transaction(function () use ($user, $party) {
            $deleted = PartyLike::query()
                ->where('party_id', $party->id)
                ->where('user_id', $user->id)
                ->delete();

            if ($deleted > 0 && $party->likes_count > 0) {
                $party->decrement('likes_count');
            }
        });

        return $party->refresh();
    }
}
