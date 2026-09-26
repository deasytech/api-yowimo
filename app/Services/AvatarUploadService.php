<?php

namespace App\Services;

use App\Exceptions\Api\AvatarUploadException;
use App\Filament\Support\ImageOptimizer;
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
     * @see \App\Filament\Support\ImageUploadField::EXTENSION_BY_MIME_TYPE
     */
    private const EXTENSION_BY_MIME_TYPE = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];

    /**
     * @throws AvatarUploadException if the file's type isn't supported or the disk write fails.
     */
    public function store(UploadedFile $file): string
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

        return Storage::disk('public')->url($path);
    }

    /**
     * Removes a previously stored avatar when it's replaced. Only ever
     * touches files this service itself stored — a user's avatar_url can
     * also be an external Clerk/OAuth-provider URL, which must never be
     * passed to a local disk delete.
     */
    public function delete(string $url): void
    {
        $prefix = Storage::disk('public')->url('');

        if (! Str::startsWith($url, $prefix)) {
            return;
        }

        Storage::disk('public')->delete(Str::after($url, $prefix));
    }
}
