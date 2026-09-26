<?php

namespace App\Console\Commands;

use Database\Seeders\TemptGamesSeeder;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

#[Signature('yowimo:sync-tempt-games {--dry-run : Only show what would be synced without saving} {--path= : Path to the extracted games JSON file}')]
#[Description('Sync games and card packs extracted from Tempt DB into Yowimo catalog.')]
class SyncTemptGamesCommand extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(TemptGamesSeeder $seeder): int
    {
        $path = $this->option('path') ?: database_path('data/tempt_extracted_games.json');

        if (! File::exists($path)) {
            $this->error("Extracted games file not found at: {$path}");

            return self::FAILURE;
        }

        $content = File::get($path);
        $data = json_decode($content, true, 512, JSON_THROW_ON_ERROR);

        $packs = $data['packs'] ?? [];
        $totalPacks = count($packs);
        $totalCards = 0;

        $this->info("Found {$totalPacks} game pack(s) in {$path}:");

        $rows = [];
        foreach ($packs as $pack) {
            $cardsCount = count($pack['cards'] ?? []);
            $totalCards += $cardsCount;
            $truthsCount = count(array_filter($pack['cards'] ?? [], fn ($c) => ($c['kind'] ?? '') === 'truth'));
            $daresCount = count(array_filter($pack['cards'] ?? [], fn ($c) => ($c['kind'] ?? '') === 'dare'));

            $rows[] = [
                $pack['name'] ?? '',
                $pack['pack_slug'] ?? '',
                $pack['game_type_slug'] ?? '',
                $pack['category'] ?? '',
                $truthsCount,
                $daresCount,
                $cardsCount,
            ];
        }

        $this->table(
            ['Pack Name', 'Pack Slug', 'Game Type Slug', 'Category', 'Truths', 'Dares', 'Total Cards'],
            $rows
        );

        $this->info("Total cards across all packs: {$totalCards}");

        if ($this->option('dry-run')) {
            $this->warn('Dry-run mode active. No changes written to database.');

            return self::SUCCESS;
        }

        $this->info('Starting database sync...');
        $seeder->run($path);
        $this->info('Successfully synced all games and card packs!');

        return self::SUCCESS;
    }
}
