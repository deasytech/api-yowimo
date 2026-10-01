<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;

/**
 * Resolves a value stored in an *_url column (cover_image_url, image_url)
 * to an absolute URL for API responses.
 *
 * The durable storage format is a disk-relative path (e.g.
 * "parties/covers/{ulid}.jpg"), computed to a full URL here, at read time,
 * so it always reflects whatever APP_URL is right now — a path stored
 * yesterday under a different dev tunnel still resolves correctly today.
 * Some rows genuinely hold an external URL instead (an admin can paste one
 * directly, and some were seeded that way) — those are already absolute and
 * are returned as-is, never re-resolved.
 */
class StoredImageUrl
{
    public static function resolve(?string $value): ?string
    {
        if ($value === null || self::isAbsoluteUrl($value)) {
            return $value;
        }

        return Storage::disk('public')->url($value);
    }

    private static function isAbsoluteUrl(string $value): bool
    {
        return (bool) preg_match('#^https?://#i', $value);
    }
}
