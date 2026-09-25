<?php

namespace App\Services\Parties;

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

    public function store(UploadedFile $file): ?string
    {
        $mimeType = $file->getMimeType();
        $extension = self::EXTENSION_BY_MIME_TYPE[$mimeType] ?? null;

        if ($extension === null) {
            return null;
        }

        $path = 'parties/covers/'.Str::ulid().'.'.$extension;

        $contents = ImageOptimizer::optimize(
            file_get_contents($file->getRealPath()),
            $mimeType,
        );

        if (! Storage::disk('public')->put($path, $contents, 'public')) {
            return null;
        }

        return Storage::disk('public')->url($path);
    }
}
