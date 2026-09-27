<?php

namespace Database\Seeders;

use App\Models\GameType;
use Illuminate\Database\Seeder;

class GameTypeDefaultPackSeeder extends Seeder
{
    /**
     * Gives every game type a starting deck. Parties created with only a
     * game_type_id inherit it (see PartyService::resolvePackId()), which is
     * what lets a game session actually deal cards — GameSessionService
     * refuses to start without a pack. Re-runnable: the cheapest active pack
     * wins, so a game type's default follows its catalog.
     */
    public function run(): void
    {
        GameType::query()->each(function (GameType $gameType): void {
            $defaultPackId = $gameType->packs()
                ->where('is_active', true)
                ->orderBy('price')
                ->orderBy('sort_order')
                ->orderBy('id')
                ->value('id');

            if ($defaultPackId !== null && $defaultPackId !== $gameType->default_pack_id) {
                $gameType->update(['default_pack_id' => $defaultPackId]);
            }
        });
    }
}
