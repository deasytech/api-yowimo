<?php

namespace Database\Seeders;

use App\Enums\PackCardKind;
use App\Enums\PackCategory;
use App\Models\GameType;
use App\Models\Pack;
use App\Models\PackCard;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class PackSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        foreach ($this->packs() as $index => $data) {
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

            $curatedCards = $this->curatedCardsFor($data['name']);

            if ($curatedCards !== null) {
                $this->seedCuratedCards($pack, $data, $curatedCards);

                continue;
            }

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

    /**
     * All curated catalog packs, in marketplace display order.
     *
     * @return array<int, array<string, mixed>>
     */
    private function packs(): array
    {
        return array_merge(
            $this->featuredPacks(),
            $this->starterPacks(),
            $this->weeklyDropPacks(),
        );
    }

    /**
     * Paid headline packs sold across the marketplace.
     *
     * @return array<int, array<string, mixed>>
     */
    private function featuredPacks(): array
    {
        return [
            $this->catalogPack('truth-dare', 'Midnight Spice', '🌶️', 'Limited', PackCategory::Spicy, 'Turn up the heat with confessions, dares, and spicy hypotheticals built for couples who want the temperature to rise fast.', 120, 30, 30, [
                ['truth', "What's the boldest thing you've ever wanted to try but haven't asked for?"],
                ['dare', "Whisper your partner's name the way you'd say it in your favorite fantasy."],
                ['truth', 'On a scale of 1-10, how adventurous are you really?'],
                ['dare', 'Trade one item of clothing with the player to your left.'],
            ]),
            $this->catalogPack('corporate', 'Office Icebreakers', '💼', 'Corporate', PackCategory::Corporate, 'Low-pressure prompts and light challenges that get a team laughing before the real meeting starts.', 60, 22, 18, [
                ['truth', "What's the most useless skill you're weirdly proud of?"],
                ['dare', 'Do your best impression of a coworker (nicely).'],
                ['truth', 'What was your first job and how much did it pay?'],
                ['dare', 'Send your favorite GIF in the team chat right now.'],
            ]),
            $this->catalogPack('couple', 'Sweet & Silly Couples', '💕', null, PackCategory::Couples, 'Playful prompts made for couples who want to laugh, blush, and learn something new about each other.', 80, 25, 15, [
                ['truth', 'What is a small thing I do that makes you feel loved?'],
                ['dare', 'Recreate our first date in under two minutes.'],
                ['truth', "What's a habit of mine you secretly find adorable?"],
                ['dare', 'Give your partner a compliment in a fake accent.'],
            ]),
            $this->catalogPack('family', 'Family Game Night', '👨‍👩‍👧', 'New', PackCategory::Family, 'Wholesome, all-ages prompts perfect for a living room full of family members of every age.', 0, 52, 52, [
                ['truth', "What's your favorite family memory from this year?"],
                ['dare', 'Do your best animal impression.'],
                ['truth', 'If you could have any superpower, what would it be?'],
                ['dare', 'Sing the chorus of your favorite song.'],
            ]),
            $this->catalogPack('party', 'Party Starter Pack', '🎉', 'Hot', PackCategory::Limited, 'A fast-moving mix of icebreakers and dares to get any party moving in the first ten minutes.', 40, 18, 22, [
                ['truth', "What's the most spontaneous thing you've ever done?"],
                ['dare', 'Start a conga line for 15 seconds.'],
                ['truth', 'Whats a trend you regret following?'],
                ['dare', 'Let the group pick your profile picture for a day.'],
            ]),
        ];
    }

    /**
     * Free starter decks for the game types that had no pack at all.
     *
     * A party created with only a game type inherits its game type's
     * default pack, and the game engine can't deal a single card
     * without one — so every playable game type needs at least this.
     *
     * @return array<int, array<string, mixed>>
     */
    private function starterPacks(): array
    {
        return [
            $this->catalogPack('most-likely', 'Most Likely To Starter', '🤔', 'Free', PackCategory::Limited, 'Point the finger: quick prompts that turn any group into a room full of suspects.', 0, 25, 15, [
                ['truth', 'Who here is most likely to become famous for something ridiculous?'],
                ['dare', 'Point at the player most likely to text their ex tonight, and explain why.'],
                ['truth', 'Who is most likely to survive a zombie apocalypse?'],
                ['dare', 'Give a dramatic acceptance speech as the group\'s most likely to win an award.'],
            ]),
            $this->catalogPack('would-rather', 'Would You Rather Starter', '⚖️', 'Free', PackCategory::Family, 'Impossible choices only. No fences to sit on, and no boring answers allowed.', 0, 30, 10, [
                ['truth', 'Would you rather always be 10 minutes late or always 20 minutes early?'],
                ['dare', 'Answer the next five questions in a movie-trailer voice.'],
                ['truth', 'Would you rather give up music or good food for a year?'],
                ['dare', 'Let the group pick the option you have to defend for the next round.'],
            ]),
            $this->catalogPack('two-truths', 'Two Truths Starter', '🎯', 'Free', PackCategory::Corporate, 'Spot the fib. Built for teams and friends who think they know each other better than they do.', 0, 51, 51, [
                ['truth', 'Tell the group two true things about yourself and one convincing lie.'],
                ['dare', 'Reveal which of your statements from the last round was the lie.'],
                ['truth', 'What is the most believable lie you have ever told?'],
                ['dare', 'Invent a fake job title for yourself and pitch it to the group.'],
            ]),
            $this->catalogPack('hot-seat', 'Hot Seat Starter', '🔥', 'Free', PackCategory::Limited, 'All eyes on you. Rapid-fire questions for whoever is currently in the chair.', 0, 35, 10, [
                ['truth', 'What is the most embarrassing thing on your phone right now?'],
                ['dare', 'Swap seats with the player who asked your last question.'],
                ['truth', 'Who in this room would you trust with a secret?'],
                ['dare', 'Let the group ask you three follow-up questions with no refusals.'],
            ]),
            $this->catalogPack('guess-song', 'Guess the Song Starter', '🎤', 'Free', PackCategory::Limited, 'Name that tune with hums, claps, and wildly off-key renditions.', 0, 15, 25, [
                ['dare', 'Hum the chorus of your favourite song until someone guesses it.'],
                ['truth', 'What song do you know every word to but would never admit to?'],
                ['dare', 'Perform a five-second drum solo from any song in the group\'s playlist.'],
                ['truth', "What's the last song you played on repeat?"],
            ]),
            $this->catalogPack('guess-movie', 'Guess the Movie Starter', '🎬', 'Free', PackCategory::Limited, 'One line, one scene, one guess. Quote it badly and make the group work for it.', 0, 15, 25, [
                ['dare', 'Act out one scene from a famous movie in under 20 seconds.'],
                ['truth', 'Which movie have you rewatched more than any other?'],
                ['dare', 'Deliver a famous movie line in the worst accent you can manage.'],
                ['truth', "What's the most overrated movie you have ever sat through?"],
            ]),
        ];
    }

    /**
     * Curated full-deck card texts keyed by pack name. When present, the deck
     * replaces the randomized factory filler so real content ships instead.
     *
     * @return array<int, array{kind: 'truth'|'dare', text: string}>|null
     */
    private function curatedCardsFor(string $packName): ?array
    {
        $file = match ($packName) {
            'Two Truths Starter' => 'two_truths_starter_cards.json',
            'Family Game Night' => 'family_game_night_cards.json',
            'Guess the Movie Starter' => 'guess_the_movie_starter_cards.json',
            'Guess the Song Starter' => 'guess_the_song_starter_cards.json',
            'Hot Seat Starter' => 'hot_seat_starter_cards.json',
            'Midnight Spice' => 'midnight_spice_cards.json',
            'Most Likely To Starter' => 'most_likely_to_starter_cards.json',
            'Neon Confessions' => 'neon_confessions_cards.json',
            'Office Icebreakers' => 'office_icebreakers_cards.json',
            'Party Starter Pack' => 'party_starter_pack_cards.json',
            'Sweet & Silly Couples' => 'sweet_and_silly_couples_cards.json',
            'Would You Rather Starter' => 'would_you_rather_starter_cards.json',
            default => null,
        };

        if ($file === null) {
            return null;
        }

        /** @var array{cards?: array<int, array{kind?: string, text?: string}>} $data */
        $data = json_decode(
            File::get(database_path('data/'.$file)),
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        $cards = array_values(array_filter(
            $data['cards'] ?? [],
            fn (array $card): bool => isset($card['text']) && trim((string) $card['text']) !== ''
                && in_array($card['kind'] ?? null, ['truth', 'dare'], true)
        ));

        /** @var array<int, array{kind: 'truth'|'dare', text: string}> $normalized */
        $normalized = array_map(
            fn (array $card): array => ['kind' => $card['kind'], 'text' => trim((string) $card['text'])],
            $cards
        );

        return $normalized === [] ? null : $normalized;
    }

    /**
     * Seed a curated deck: the four preview prompts first, then the curated
     * texts that do not duplicate them. Counts reflect what was persisted.
     *
     * @param  array{preview: array<int, array{0: 'truth'|'dare', 1: string}>, truths: int, dares: int}  $data
     * @param  array<int, array{kind: 'truth'|'dare', text: string}>  $curatedCards
     */
    private function seedCuratedCards(Pack $pack, array $data, array $curatedCards): void
    {
        $preview = $data['preview'];

        foreach ($preview as $position => [$kind, $text]) {
            PackCard::query()->create([
                'pack_id' => $pack->id,
                'kind' => $kind === 'truth' ? PackCardKind::Truth : PackCardKind::Dare,
                'text' => $text,
                'position' => $position,
                'is_preview' => true,
            ]);
        }

        $previewTexts = array_fill_keys(array_column(array_map(
            fn (array $card): array => ['text' => trim($card[1])],
            $preview
        ), 'text'), true);

        $position = count($preview);
        $curatedTruths = 0;
        $curatedDares = 0;

        foreach ($curatedCards as $card) {
            if (isset($previewTexts[$card['text']])) {
                continue;
            }

            $previewTexts[$card['text']] = true;
            $kind = $card['kind'] === 'truth' ? PackCardKind::Truth : PackCardKind::Dare;

            PackCard::query()->create([
                'pack_id' => $pack->id,
                'kind' => $kind,
                'text' => $card['text'],
                'position' => $position++,
                'is_preview' => false,
            ]);

            if ($kind === PackCardKind::Truth) {
                $curatedTruths++;
            } else {
                $curatedDares++;
            }
        }

        $previewTruths = count(array_filter($preview, fn (array $card) => $card[0] === 'truth'));
        $previewDares = count($preview) - $previewTruths;

        $remainingTruths = max($data['truths'] - ($previewTruths + $curatedTruths), 0);
        $remainingDares = max($data['dares'] - ($previewDares + $curatedDares), 0);

        PackCard::factory()
            ->count($remainingTruths)
            ->sequence(fn ($sequence) => ['position' => $position + $sequence->index])
            ->state(['pack_id' => $pack->id, 'kind' => PackCardKind::Truth, 'is_preview' => false])
            ->create();

        PackCard::factory()
            ->count($remainingDares)
            ->sequence(fn ($sequence) => ['position' => $position + $remainingTruths + $sequence->index])
            ->state(['pack_id' => $pack->id, 'kind' => PackCardKind::Dare, 'is_preview' => false])
            ->create();

        $finalTruths = $previewTruths + $curatedTruths + $remainingTruths;
        $finalDares = $previewDares + $curatedDares + $remainingDares;

        $pack->update([
            'truths_count' => $finalTruths,
            'dares_count' => $finalDares,
            'cards_count' => $finalTruths + $finalDares,
        ]);
    }

    /**
     * This week's exclusive paid drop, listed last in the catalog.
     *
     * @return array<int, array<string, mixed>>
     */
    private function weeklyDropPacks(): array
    {
        return [
            $this->catalogPack('wild', 'Neon Confessions', '💫', 'Drop of the Week', PackCategory::Limited, "This week's exclusive drop — neon-lit confessions, blackout dares, and prompts that only surface for 48 hours.", 300, 70, 50, [
                ['truth', "What's a confession you've never told anyone in this room?"],
                ['dare', 'Let the group send one text from your phone.'],
                ['truth', "What's the wildest rumor you've heard about yourself?"],
                ['dare', 'Do 20 seconds of your best dance move.'],
            ], true),
        ];
    }

    /**
     * @param  array<int, array{0: 'truth'|'dare', 1: string}>  $preview
     * @return array{game_type_slug: string, name: string, emoji: string, tag: ?string, category: PackCategory, description: string, price: int, truths: int, dares: int, is_featured: bool, preview: array<int, array{0: 'truth'|'dare', 1: string}>}
     */
    private function catalogPack(
        string $gameTypeSlug,
        string $name,
        string $emoji,
        ?string $tag,
        PackCategory $category,
        string $description,
        int $price,
        int $truths,
        int $dares,
        array $preview,
        bool $isFeatured = false,
    ): array {
        return [
            'game_type_slug' => $gameTypeSlug,
            'name' => $name,
            'emoji' => $emoji,
            'tag' => $tag,
            'category' => $category,
            'description' => $description,
            'price' => $price,
            'truths' => $truths,
            'dares' => $dares,
            'is_featured' => $isFeatured,
            'preview' => $preview,
        ];
    }
}
