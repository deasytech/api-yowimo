<?php

namespace Database\Seeders;

use App\Enums\PackCardKind;
use App\Enums\PackCategory;
use App\Models\GameType;
use App\Models\Pack;
use App\Models\PackCard;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class PackSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $packs = [
            [
                'game_type_slug' => 'truth-dare',
                'name' => 'Midnight Spice',
                'emoji' => '🌶️',
                'tag' => 'Limited',
                'category' => PackCategory::Spicy,
                'description' => 'Turn up the heat with confessions, dares, and spicy hypotheticals built for couples who want the temperature to rise fast.',
                'price' => 120,
                'truths' => 30,
                'dares' => 30,
                'is_featured' => false,
                'preview' => [
                    ['truth', "What's the boldest thing you've ever wanted to try but haven't asked for?"],
                    ['dare', "Whisper your partner's name the way you'd say it in your favorite fantasy."],
                    ['truth', 'On a scale of 1-10, how adventurous are you really?'],
                    ['dare', 'Trade one item of clothing with the player to your left.'],
                ],
            ],
            [
                'game_type_slug' => 'corporate',
                'name' => 'Office Icebreakers',
                'emoji' => '💼',
                'tag' => 'Corporate',
                'category' => PackCategory::Corporate,
                'description' => 'Low-pressure prompts and light challenges that get a team laughing before the real meeting starts.',
                'price' => 60,
                'truths' => 22,
                'dares' => 18,
                'is_featured' => false,
                'preview' => [
                    ['truth', "What's the most useless skill you're weirdly proud of?"],
                    ['dare', 'Do your best impression of a coworker (nicely).'],
                    ['truth', 'What was your first job and how much did it pay?'],
                    ['dare', 'Send your favorite GIF in the team chat right now.'],
                ],
            ],
            [
                'game_type_slug' => 'couple',
                'name' => 'Sweet & Silly Couples',
                'emoji' => '💕',
                'tag' => null,
                'category' => PackCategory::Couples,
                'description' => 'Playful prompts made for couples who want to laugh, blush, and learn something new about each other.',
                'price' => 80,
                'truths' => 25,
                'dares' => 15,
                'is_featured' => false,
                'preview' => [
                    ['truth', 'What is a small thing I do that makes you feel loved?'],
                    ['dare', 'Recreate our first date in under two minutes.'],
                    ['truth', "What's a habit of mine you secretly find adorable?"],
                    ['dare', 'Give your partner a compliment in a fake accent.'],
                ],
            ],
            [
                'game_type_slug' => 'family',
                'name' => 'Family Game Night',
                'emoji' => '👨‍👩‍👧',
                'tag' => 'New',
                'category' => PackCategory::Family,
                'description' => 'Wholesome, all-ages prompts perfect for a living room full of family members of every age.',
                'price' => 0,
                'truths' => 20,
                'dares' => 20,
                'is_featured' => false,
                'preview' => [
                    ['truth', "What's your favorite family memory from this year?"],
                    ['dare', 'Do your best animal impression.'],
                    ['truth', 'If you could have any superpower, what would it be?'],
                    ['dare', 'Sing the chorus of your favorite song.'],
                ],
            ],
            [
                'game_type_slug' => 'party',
                'name' => 'Party Starter Pack',
                'emoji' => '🎉',
                'tag' => 'Hot',
                'category' => PackCategory::Limited,
                'description' => 'A fast-moving mix of icebreakers and dares to get any party moving in the first ten minutes.',
                'price' => 40,
                'truths' => 18,
                'dares' => 22,
                'is_featured' => false,
                'preview' => [
                    ['truth', "What's the most spontaneous thing you've ever done?"],
                    ['dare', 'Start a conga line for 15 seconds.'],
                    ['truth', 'Whats a trend you regret following?'],
                    ['dare', 'Let the group pick your profile picture for a day.'],
                ],
            ],
            [
                'game_type_slug' => 'wild',
                'name' => 'Neon Confessions',
                'emoji' => '💫',
                'tag' => 'Drop of the Week',
                'category' => PackCategory::Limited,
                'description' => "This week's exclusive drop — neon-lit confessions, blackout dares, and prompts that only surface for 48 hours.",
                'price' => 300,
                'truths' => 70,
                'dares' => 50,
                'is_featured' => true,
                'preview' => [
                    ['truth', "What's a confession you've never told anyone in this room?"],
                    ['dare', 'Let the group send one text from your phone.'],
                    ['truth', "What's the wildest rumor you've heard about yourself?"],
                    ['dare', 'Do 20 seconds of your best dance move.'],
                ],
            ],
        ];

        foreach ($packs as $index => $data) {
            $gameType = GameType::query()->where('slug', $data['game_type_slug'])->first();

            /** @var Pack $pack */
            $pack = Pack::query()->updateOrCreate(
                ['slug' => Str::slug($data['name'])],
                [
                    'game_type_id' => $gameType?->id,
                    'name' => $data['name'],
                    'emoji' => $data['emoji'],
                    'tag' => $data['tag'],
                    'category' => $data['category'],
                    'description' => $data['description'],
                    'price' => $data['price'],
                    'truths_count' => $data['truths'],
                    'dares_count' => $data['dares'],
                    'cards_count' => $data['truths'] + $data['dares'],
                    'cover_image_url' => null,
                    'gradient' => ['#D84CFF', '#FF8A2A'],
                    'is_featured' => $data['is_featured'],
                    'is_active' => true,
                    'sort_order' => $index,
                ]
            );

            $pack->cards()->delete();

            foreach ($data['preview'] as $position => [$kind, $text]) {
                PackCard::query()->create([
                    'pack_id' => $pack->id,
                    'kind' => $kind === 'truth' ? PackCardKind::Truth : PackCardKind::Dare,
                    'text' => $text,
                    'position' => $position,
                    'is_preview' => true,
                ]);
            }

            $previewTruths = count(array_filter($data['preview'], fn (array $card) => $card[0] === 'truth'));
            $previewDares = count(array_filter($data['preview'], fn (array $card) => $card[0] === 'dare'));

            $remainingTruths = max($data['truths'] - $previewTruths, 0);
            $remainingDares = max($data['dares'] - $previewDares, 0);
            $previewCount = count($data['preview']);

            PackCard::factory()
                ->count($remainingTruths)
                ->sequence(fn ($sequence) => ['position' => $previewCount + $sequence->index])
                ->state(['pack_id' => $pack->id, 'kind' => PackCardKind::Truth, 'is_preview' => false])
                ->create();

            PackCard::factory()
                ->count($remainingDares)
                ->sequence(fn ($sequence) => ['position' => $previewCount + $remainingTruths + $sequence->index])
                ->state(['pack_id' => $pack->id, 'kind' => PackCardKind::Dare, 'is_preview' => false])
                ->create();
        }

        // A handful of extra, fully randomized packs to round out the marketplace.
        $gameTypeIds = GameType::query()->pluck('id');

        $marketplacePacks = Pack::factory()
            ->count(6)
            ->state(fn () => ['game_type_id' => $gameTypeIds->random()])
            ->has(PackCard::factory()->count(10), 'cards')
            ->create();

        // The 10 attached cards' truth/dare split is randomized per-card, so
        // sync the pack's own count metadata to what was actually persisted.
        $marketplacePacks->each(function (Pack $pack) {
            $truths = $pack->cards()->where('kind', PackCardKind::Truth)->count();
            $dares = $pack->cards()->where('kind', PackCardKind::Dare)->count();

            $pack->update([
                'truths_count' => $truths,
                'dares_count' => $dares,
                'cards_count' => $truths + $dares,
            ]);
        });
    }
}
