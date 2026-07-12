<?php

namespace Database\Seeders;

use App\Enums\PartyMode;
use App\Enums\PartyStatus;
use App\Enums\PartyVisibility;
use App\Models\GameType;
use App\Models\Party;
use App\Models\PartyLike;
use App\Models\User;
use Illuminate\Database\Seeder;

class PartySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $hosts = User::factory()->count(10)->create();
        $gameTypeIds = GameType::query()->pluck('id');

        $titles = [
            'Friday Night Chaos 🔥', 'Dorm 4B vs The World', 'Acme Team Quarterly',
            'Sunday Brunch Truths', 'Late Night Confessions', 'Rooftop Rager',
            'Study Break Showdown', 'Newlyweds Game Night', 'Uni Squad Mixer',
            'Remote Team Icebreaker', 'Girls Night In', 'Boys Night Chaos',
            'Family Reunion Fun', 'Blind Date Roulette', 'Neighborhood Meetup',
            'Book Club Bonus Round', 'Startup Offsite Warmup', 'Beach House Bash',
            'Trivia & Truths', 'Wildcard Wednesday',
        ];

        $parties = [];

        foreach ($titles as $index => $title) {
            $mode = PartyMode::cases()[$index % count(PartyMode::cases())];
            $visibility = $index % 4 === 0 ? PartyVisibility::Private : PartyVisibility::Public;
            $status = match (true) {
                $index % 7 === 0 => PartyStatus::Draft,
                $index % 5 === 0 => PartyStatus::Live,
                default => PartyStatus::Scheduled,
            };

            $parties[] = Party::factory()
                ->state([
                    'host_id' => $hosts->random()->id,
                    'game_type_id' => $gameTypeIds->random(),
                    'title' => $title,
                    'mode' => $mode,
                    'visibility' => $visibility,
                    'status' => $status,
                    'is_sponsored' => $index === 2,
                    'sponsor_name' => $index === 2 ? 'Acme Co.' : null,
                ])
                ->create();
        }

        // Sprinkle in likes from random users on public parties.
        $publicParties = collect($parties)->filter(fn (Party $party) => $party->visibility === PartyVisibility::Public);
        $likers = User::factory()->count(15)->create();

        foreach ($publicParties as $party) {
            $likers->random(random_int(0, min(10, $likers->count())))
                ->unique('id')
                ->each(function (User $liker) use ($party) {
                    PartyLike::query()->firstOrCreate([
                        'party_id' => $party->id,
                        'user_id' => $liker->id,
                    ]);
                });

            $party->update(['likes_count' => $party->likes()->count()]);
        }
    }
}
