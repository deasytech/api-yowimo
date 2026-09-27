<?php

namespace Database\Seeders;

use App\Enums\PackCardKind;
use App\Enums\PackCategory;
use App\Models\GameType;
use App\Models\Pack;
use App\Models\PackCard;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class TemptGamesSeeder extends Seeder
{
    /**
     * Seed the extracted Tempt Games catalog without touching existing cards.
     */
    public function run(?string $jsonPath = null): void
    {
        $packs = $this->extractPacks($jsonPath ?? database_path('data/tempt_extracted_games.json'));

        DB::transaction(function () use ($packs): void {
            $baseSortOrder = (int) (Pack::query()->max('sort_order') ?? 0);

            foreach ($packs as $packIndex => $packData) {
                $this->syncPack((int) $packIndex, $packData, $baseSortOrder);
            }
        });
    }

    /**
     * Read the extracted games JSON and return its validated packs payload.
     *
     * @return array<int, array<string, mixed>>
     *
     * @throws TemptGamesExtractException when the file is missing or malformed
     */
    public function extractPacks(string $path): array
    {
        if (! File::exists($path)) {
            throw TemptGamesExtractException::notFound($path);
        }

        $data = json_decode(File::get($path), true, 512, JSON_THROW_ON_ERROR);

        if (! isset($data['packs']) || ! is_array($data['packs'])) {
            throw TemptGamesExtractException::missingPacksArray($path);
        }

        return $data['packs'];
    }

    /**
     * Upsert one extracted pack along with its unseen cards and counters.
     *
     * @param  array<string, mixed>  $packData
     */
    private function syncPack(int $packIndex, array $packData, int $baseSortOrder): void
    {
        $gameType = GameType::query()->where('slug', $packData['game_type_slug'])->first();

        if (! $gameType) {
            return;
        }

        /** @var Pack $pack */
        $pack = Pack::query()->firstOrNew(['slug' => $packData['pack_slug']]);

        $this->fillPack($pack, $packData, $gameType, $packIndex, $baseSortOrder);
        $this->insertUnseenCards($pack, $packData);
        $this->refreshCounters($pack);
    }

    /**
     * @param  array<string, mixed>  $packData
     */
    private function fillPack(Pack $pack, array $packData, GameType $gameType, int $packIndex, int $baseSortOrder): void
    {
        $category = PackCategory::tryFrom($packData['category']) ?? PackCategory::Limited;

        $attributes = [
            'game_type_id' => $gameType->id,
            'name' => $packData['name'],
            'emoji' => $packData['emoji'] ?? '🎮',
            'tag' => $packData['tag'] ?? 'Featured',
            'category' => $category,
            'description' => $packData['description'] ?? '',
            'price' => (int) ($packData['price'] ?? 0),
            'truths_count' => 0,
            'dares_count' => 0,
            'cards_count' => 0,
            'cover_image_url' => null,
            'gradient' => ['#D84CFF', '#FF8A2A'],
            'is_featured' => (bool) ($packData['is_featured'] ?? false),
            'is_active' => true,
        ];

        // Catalog position only applies to packs created by this sync;
        // reruns must leave the existing catalog order untouched.
        if (! $pack->exists) {
            $attributes['sort_order'] = $baseSortOrder + $packIndex + 1;
        }

        $pack->fill($attributes)->save();
    }

    /**
     * Bulk-insert the extracted cards that the pack does not hold yet.
     *
     * @param  array<string, mixed>  $packData
     */
    private function insertUnseenCards(Pack $pack, array $packData): void
    {
        $existingTexts = PackCard::query()
            ->where('pack_id', $pack->id)
            ->pluck('text')
            ->all();

        $existingTextsLookup = array_fill_keys($existingTexts, true);
        $currentPosition = count($existingTexts);

        $cardsToInsert = [];
        $now = now();

        foreach ($packData['cards'] ?? [] as $card) {
            $rawText = $this->usableCardText($card);

            if ($rawText === null || isset($existingTextsLookup[$rawText])) {
                continue;
            }

            $existingTextsLookup[$rawText] = true;
            $kind = ($card['kind'] ?? 'truth') === 'dare' ? PackCardKind::Dare : PackCardKind::Truth;

            $cardsToInsert[] = [
                'pack_id' => $pack->id,
                'kind' => $kind->value,
                'text' => $rawText,
                'position' => $currentPosition++,
                'is_preview' => $currentPosition <= 4,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        foreach (array_chunk($cardsToInsert, 100) as $chunk) {
            PackCard::query()->insert($chunk);
        }
    }

    /**
     * Normalize a raw extracted card text, or return null when it must drop.
     *
     * Empty texts and the literal "merge" placeholder a raw Tempt extract can
     * carry are dropped. Only the exact placeholder is skipped, so ordinary
     * words containing "merge" — such as "emergency" — are preserved.
     *
     * @param  array<string, mixed>  $card
     */
    private function usableCardText(array $card): ?string
    {
        $rawText = trim((string) ($card['text'] ?? ''));

        return ($rawText === '' || strcasecmp($rawText, 'merge') === 0) ? null : $rawText;
    }

    /**
     * Re-point the pack's count metadata at the cards actually persisted.
     */
    private function refreshCounters(Pack $pack): void
    {
        $truths = $pack->cards()->where('kind', PackCardKind::Truth)->count();
        $dares = $pack->cards()->where('kind', PackCardKind::Dare)->count();

        $pack->update([
            'truths_count' => $truths,
            'dares_count' => $dares,
            'cards_count' => $truths + $dares,
        ]);
    }
}
