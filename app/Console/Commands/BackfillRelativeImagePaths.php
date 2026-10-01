<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * One-time fix for rows written before cover_image_url/image_url/avatar_url
 * switched to storing a disk-relative path (see StoredImageUrl): those
 * columns hold a URL resolved against whatever APP_URL/tunnel was active at
 * upload time, which breaks the moment that URL changes. Only rows matching
 * the "/storage/" segment every locally-resolved URL has are touched; a
 * genuine external URL (an admin-pasted link, Clerk's hosted avatar CDN,
 * seeded data) never contains it and is left alone.
 */
#[Signature('images:backfill-relative-paths')]
#[Description('Converts locally-resolved absolute image URLs back into disk-relative paths, leaving external URLs untouched.')]
class BackfillRelativeImagePaths extends Command
{
    /**
     * @var array<string, string>
     */
    private const COLUMN_BY_TABLE = [
        'parties' => 'cover_image_url',
        'packs' => 'cover_image_url',
        'game_types' => 'image_url',
        'users' => 'avatar_url',
    ];

    public function handle(): int
    {
        foreach (self::COLUMN_BY_TABLE as $table => $column) {
            $updated = 0;

            DB::table($table)
                ->whereNotNull($column)
                ->where($column, 'like', '%/storage/%')
                ->orderBy('id')
                ->each(function ($row) use ($table, $column, &$updated) {
                    DB::table($table)->where('id', $row->id)->update([
                        $column => Str::after($row->{$column}, '/storage/'),
                    ]);

                    $updated++;
                });

            $this->info("{$table}.{$column}: backfilled {$updated} row(s).");
        }

        return self::SUCCESS;
    }
}
