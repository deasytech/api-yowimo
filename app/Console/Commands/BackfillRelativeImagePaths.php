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
 * upload time, which breaks the moment that URL changes.
 *
 * Matching can't anchor to the *current* Storage::disk('public')->url('')
 * prefix — these rows were written under a different (often since-rotated)
 * APP_URL/tunnel than whatever is active now, which is exactly why they need
 * backfilling. Instead, a row is only touched when, after the host-agnostic
 * "/storage/" segment every locally-resolved URL has, what follows starts
 * with the specific upload directory this app actually writes that column's
 * files to (see UPLOAD_PREFIX_BY_COLUMN) — an external URL that happens to
 * contain "/storage/" (a CDN path segment, say) won't also happen to be
 * followed by one of these exact directory names, so it's left alone.
 */
#[Signature('images:backfill-relative-paths')]
#[Description('Converts locally-resolved absolute image URLs back into disk-relative paths, leaving external URLs untouched.')]
class BackfillRelativeImagePaths extends Command
{
    /**
     * @var array<string, array{column: string, prefix: string}>
     */
    private const UPLOAD_PREFIX_BY_TABLE = [
        // 'parties/' covers both PartyCoverImageService's API uploads
        // ("parties/covers/...") and the admin form's ("parties/...").
        'parties' => ['column' => 'cover_image_url', 'prefix' => 'parties/'],
        'packs' => ['column' => 'cover_image_url', 'prefix' => 'packs/'],
        'game_types' => ['column' => 'image_url', 'prefix' => 'game-types/'],
        'users' => ['column' => 'avatar_url', 'prefix' => 'avatars/'],
    ];

    public function handle(): int
    {
        foreach (self::UPLOAD_PREFIX_BY_TABLE as $table => ['column' => $column, 'prefix' => $prefix]) {
            $updated = 0;
            $skipped = 0;

            DB::table($table)
                ->whereNotNull($column)
                ->where($column, 'like', "%/storage/{$prefix}%")
                ->orderBy('id')
                ->each(function ($row) use ($table, $column, $prefix, &$updated, &$skipped) {
                    $relativePath = Str::after($row->{$column}, '/storage/');

                    // Guards against a coincidental "/storage/" match further
                    // into an otherwise-unrelated external URL.
                    if (! str_starts_with($relativePath, $prefix)) {
                        $skipped++;

                        return;
                    }

                    DB::table($table)->where('id', $row->id)->update([$column => $relativePath]);
                    $updated++;
                });

            $message = "{$table}.{$column}: backfilled {$updated} row(s).";
            $this->info($skipped ? "{$message} Skipped {$skipped} ambiguous match(es)." : $message);
        }

        return self::SUCCESS;
    }
}
