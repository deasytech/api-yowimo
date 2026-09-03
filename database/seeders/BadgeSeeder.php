<?php

namespace Database\Seeders;

use App\Enums\BadgeKey;
use App\Models\Badge;
use Illuminate\Database\Seeder;

class BadgeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $badges = [
            ['key' => BadgeKey::FirstParty, 'name' => 'First Party', 'description' => 'Completed your first game.', 'icon' => '🎉'],
            ['key' => BadgeKey::HundredParties, 'name' => '100 Parties', 'description' => 'Completed 100 games.', 'icon' => '💯'],
            ['key' => BadgeKey::PerfectGame, 'name' => 'Perfect Game', 'description' => 'Completed a game without a single skipped turn.', 'icon' => '✨'],
            ['key' => BadgeKey::PartyKing, 'name' => 'Party King', 'description' => 'Earned the MVP bonus in a game.', 'icon' => '👑'],
            ['key' => BadgeKey::TruthMaster, 'name' => 'Truth Master', 'description' => 'Completed 25 Truth challenges.', 'icon' => '🗣️'],
            ['key' => BadgeKey::DareDevil, 'name' => 'Dare Devil', 'description' => 'Completed 25 Dare challenges.', 'icon' => '😈'],
            ['key' => BadgeKey::SocialButterfly, 'name' => 'Social Butterfly', 'description' => 'Made 10 friends.', 'icon' => '🦋'],
        ];

        foreach ($badges as $badge) {
            Badge::query()->updateOrCreate(
                ['key' => $badge['key']],
                [
                    'name' => $badge['name'],
                    'description' => $badge['description'],
                    'icon' => $badge['icon'],
                ]
            );
        }
    }
}
