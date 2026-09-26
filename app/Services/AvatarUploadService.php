<?php

namespace App\Services;

use App\Exceptions\Api\AvatarUploadException;
use App\Filament\Support\ImageOptimizer;
use App\Filament\Support\ImageUploadField;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Stores a user's uploaded avatar, re-encoded/resized the same way a party
 * cover image is (see ImageOptimizer/PartyCoverImageService), and returns
 * its public URL.
 */
class AvatarUploadService
{
    /**
     * @see ImageUploadField::EXTENSION_BY_MIME_TYPE
     */
    private const EXTENSION_BY_MIME_TYPE = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];

    /**
     * @return array{url: string, path: string}
     *
     * @throws AvatarUploadException if the file's type isn't supported or the disk write fails.
     */
    public function store(UploadedFile $file): array
    {
        $mimeType = $file->getMimeType();
        $extension = self::EXTENSION_BY_MIME_TYPE[$mimeType] ?? null;

        if ($extension === null) {
            throw new AvatarUploadException("Unsupported avatar image type: {$mimeType}.");
        }

        $path = 'avatars/'.Str::ulid().'.'.$extension;

        $contents = ImageOptimizer::optimize(
            file_get_contents($file->getRealPath()),
            $mimeType,
        );

        if (! Storage::disk('public')->put($path, $contents, 'public')) {
            throw new AvatarUploadException('Failed to store the uploaded avatar.');
        }

        return [
            'url' => Storage::disk('public')->url($path),
            'path' => $path,
        ];
    }

    /**
     * Removes a previously stored avatar when it's replaced or orphaned by a
     * failed save. Takes the storage path this service itself returned from
     * store() (tracked separately on the user, in `avatar_path`) — never a
     * URL, since a user's avatar_url is a free-text field that can be set to
     * anything (including another user's real avatar URL) and must never be
     * trusted to decide what gets deleted from disk.
     */
    public function delete(string $path): void
    {
        Storage::disk('public')->delete($path);
    }
}
