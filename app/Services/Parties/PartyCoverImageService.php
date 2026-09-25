<?php

namespace App\Services\Parties;

use App\Exceptions\Api\PartyCoverImageUploadException;
use App\Filament\Support\ImageOptimizer;
use App\Filament\Support\ImageUploadField;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Stores a party's cover image upload, re-encoded/resized the same way an
 * admin-uploaded image is (see ImageOptimizer), and returns its public URL.
 */
class PartyCoverImageService
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
     * @throws PartyCoverImageUploadException if the file's type isn't supported or the disk write fails.
     */
    public function store(UploadedFile $file): string
    {
        $mimeType = $file->getMimeType();
        $extension = self::EXTENSION_BY_MIME_TYPE[$mimeType] ?? null;

        if ($extension === null) {
            throw new PartyCoverImageUploadException("Unsupported cover image type: {$mimeType}.");
        }

        $path = 'parties/covers/'.Str::ulid().'.'.$extension;

        $contents = ImageOptimizer::optimize(
            file_get_contents($file->getRealPath()),
            $mimeType,
        );

        if (! Storage::disk('public')->put($path, $contents, 'public')) {
            throw new PartyCoverImageUploadException('Failed to store the uploaded cover image.');
        }

        return Storage::disk('public')->url($path);
    }

    /**
     * Removes a previously stored cover image — used to clean up an upload
     * that already succeeded when party creation fails afterward, so it
     * doesn't stay orphaned on disk.
     */
    public function delete(string $url): void
    {
        Storage::disk('public')->delete(
            Str::after($url, Storage::disk('public')->url(''))
        );
    }
}
