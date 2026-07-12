<?php

namespace Database\Seeders;

use App\Enums\GameIntensity;
use App\Models\GameType;
use Illuminate\Database\Seeder;

class GameTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $gameTypes = [
            ['slug' => 'truth-dare', 'name' => 'Truth or Dare', 'emoji' => '🎭', 'tagline' => 'Spill or risk it.', 'audience' => 'Friends', 'intensity' => GameIntensity::Wild, 'cost' => 0],
            ['slug' => 'never-have-i', 'name' => 'Never Have I Ever', 'emoji' => '🙊', 'tagline' => 'Confess in style.', 'audience' => 'Friends', 'intensity' => GameIntensity::Medium, 'cost' => 0],
            ['slug' => 'most-likely', 'name' => 'Most Likely To', 'emoji' => '🤔', 'tagline' => 'Point fingers, laugh loud.', 'audience' => 'Friends', 'intensity' => GameIntensity::Medium, 'cost' => 0],
            ['slug' => 'would-rather', 'name' => 'Would You Rather', 'emoji' => '⚖️', 'tagline' => 'Impossible choices only.', 'audience' => 'All', 'intensity' => GameIntensity::Chill, 'cost' => 0],
            ['slug' => 'two-truths', 'name' => 'Two Truths and a Lie', 'emoji' => '🎯', 'tagline' => 'Spot the fib.', 'audience' => 'All', 'intensity' => GameIntensity::Chill, 'cost' => 10],
            ['slug' => 'rapid-fire', 'name' => 'Rapid Fire', 'emoji' => '⚡', 'tagline' => 'Answer before the buzzer.', 'audience' => 'Friends', 'intensity' => GameIntensity::Medium, 'cost' => 10],
            ['slug' => 'charades', 'name' => 'Charades', 'emoji' => '🃏', 'tagline' => 'Act it out.', 'audience' => 'Family', 'intensity' => GameIntensity::Chill, 'cost' => 0],
            ['slug' => 'hot-seat', 'name' => 'Hot Seat', 'emoji' => '🔥', 'tagline' => 'All eyes on you.', 'audience' => 'Friends', 'intensity' => GameIntensity::Wild, 'cost' => 10],
            ['slug' => 'guess-song', 'name' => 'Guess the Song', 'emoji' => '🎤', 'tagline' => 'Name that tune.', 'audience' => 'All', 'intensity' => GameIntensity::Chill, 'cost' => 20],
            ['slug' => 'guess-movie', 'name' => 'Guess the Movie', 'emoji' => '🎬', 'tagline' => 'One clip, one guess.', 'audience' => 'All', 'intensity' => GameIntensity::Chill, 'cost' => 20],
            ['slug' => 'couple', 'name' => 'Couple Quiz', 'emoji' => '💕', 'tagline' => 'How well do you know them?', 'audience' => 'Couples', 'intensity' => GameIntensity::Medium, 'cost' => 0],
            ['slug' => 'family', 'name' => 'Family Feud Night', 'emoji' => '👨‍👩‍👧', 'tagline' => 'Survey says...', 'audience' => 'Family', 'intensity' => GameIntensity::Chill, 'cost' => 0],
            ['slug' => 'corporate', 'name' => 'Corporate Icebreaker', 'emoji' => '💼', 'tagline' => 'Low-pressure team bonding.', 'audience' => 'Teams', 'intensity' => GameIntensity::Chill, 'cost' => 0],
            ['slug' => 'party', 'name' => 'Party Mixer', 'emoji' => '🎉', 'tagline' => 'Break the ice fast.', 'audience' => 'All', 'intensity' => GameIntensity::Medium, 'cost' => 0],
            ['slug' => 'wild', 'name' => 'Wildcard Mayhem', 'emoji' => '🃏', 'tagline' => 'Anything goes.', 'audience' => 'Adults', 'intensity' => GameIntensity::Wild, 'cost' => 50],
        ];

        foreach ($gameTypes as $index => $gameType) {
            GameType::query()->updateOrCreate(
                ['slug' => $gameType['slug']],
                [
                    'name' => $gameType['name'],
                    'emoji' => $gameType['emoji'],
                    'tagline' => $gameType['tagline'],
                    'audience' => $gameType['audience'],
                    'intensity' => $gameType['intensity'],
                    'cost' => $gameType['cost'],
                    'image_url' => null,
                    'gradient' => ['#7A1EFF', '#FF8A2A'],
                    'is_active' => true,
                    'sort_order' => $index,
                ]
            );
        }
    }
}
