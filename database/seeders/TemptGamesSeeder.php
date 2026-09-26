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
use RuntimeException;

class TemptGamesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(?string $jsonPath = null): void
    {
        $path = $jsonPath ?? database_path('data/tempt_extracted_games.json');

        if (! File::exists($path)) {
            throw new RuntimeException("Tempt extracted games JSON file not found at: {$path}");
        }

        $content = File::get($path);
        $data = json_decode($content, true, 512, JSON_THROW_ON_ERROR);

        if (! isset($data['packs']) || ! is_array($data['packs'])) {
            throw new RuntimeException("Invalid JSON format in {$path}: missing 'packs' array.");
        }

        DB::transaction(function () use ($data): void {
            $baseSortOrder = (int) (Pack::query()->max('sort_order') ?? 0);

            foreach ($data['packs'] as $packIndex => $packData) {
                $gameType = GameType::query()->where('slug', $packData['game_type_slug'])->first();

                if (! $gameType) {
                    continue;
                }

                $category = PackCategory::tryFrom($packData['category']) ?? PackCategory::Limited;

                /** @var Pack $pack */
                $pack = Pack::query()->updateOrCreate(
                    ['slug' => $packData['pack_slug']],
                    [
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
                        'sort_order' => $baseSortOrder + $packIndex + 1,
                    ]
                );

                $existingTexts = PackCard::query()
                    ->where('pack_id', $pack->id)
                    ->pluck('text')
                    ->all();

                $existingTextsLookup = array_fill_keys($existingTexts, true);
                $currentPosition = count($existingTexts);

                $cardsToInsert = [];
                $now = now();

                foreach ($packData['cards'] as $card) {
                    $rawText = trim((string) ($card['text'] ?? ''));

                    if ($rawText === '' || stripos($rawText, 'merge') !== false) {
                        continue;
                    }

                    if (isset($existingTextsLookup[$rawText])) {
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

                if (! empty($cardsToInsert)) {
                    foreach (array_chunk($cardsToInsert, 100) as $chunk) {
                        PackCard::query()->insert($chunk);
                    }
                }

                $actualTruths = $pack->cards()->where('kind', PackCardKind::Truth)->count();
                $actualDares = $pack->cards()->where('kind', PackCardKind::Dare)->count();

                $pack->update([
                    'truths_count' => $actualTruths,
                    'dares_count' => $actualDares,
                    'cards_count' => $actualTruths + $actualDares,
                ]);
            }
        });
    }
}
