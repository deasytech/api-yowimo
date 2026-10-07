<?php

namespace Tests\Support;

use App\Enums\PackCardKind;
use App\Enums\PartyStatus;
use App\Models\Pack;
use App\Models\PackCard;
use App\Models\Party;
use App\Models\PartyMember;
use App\Models\User;
use Illuminate\Support\Collection;

trait MakesLiveGameSessionParties
{
    /**
     * A free, always-playable pack with $cardsPerKind cards of each kind.
     */
    public function makePlayablePack(int $cardsPerKind = 20): Pack
    {
        $pack = Pack::factory()->create(['price' => 0]);
        PackCard::factory()->count($cardsPerKind)->create(['pack_id' => $pack->id, 'kind' => PackCardKind::Truth]);
        PackCard::factory()->count($cardsPerKind)->create(['pack_id' => $pack->id, 'kind' => PackCardKind::Dare]);

        return $pack;
    }

    /**
     * A live party with $memberCount members (the host plus $memberCount - 1
     * others), on a freshly built playable pack unless one is given.
     *
     * @param  array<string, mixed>  $partyAttributes  Extra Party::factory() overrides.
     * @return array{0: User, 1: Party, 2: Collection<int, int>} [host, party, member user ids]
     */
    public function makeLiveGameSessionParty(
        int $memberCount = 1,
        int $cardsPerKind = 20,
        ?User $host = null,
        ?Pack $pack = null,
        array $partyAttributes = [],
    ): array {
        $pack ??= $this->makePlayablePack($cardsPerKind);
        $host ??= User::factory()->create();

        $party = Party::factory()->create([
            'host_id' => $host->id,
            'pack_id' => $pack->id,
            'status' => PartyStatus::Live,
            ...$partyAttributes,
        ]);

        $members = collect([PartyMember::factory()->create(['party_id' => $party->id, 'user_id' => $host->id])]);
        for ($i = 1; $i < $memberCount; $i++) {
            $members->push(PartyMember::factory()->create(['party_id' => $party->id]));
        }

        return [$host, $party->fresh(), $members->pluck('user_id')];
    }
}
