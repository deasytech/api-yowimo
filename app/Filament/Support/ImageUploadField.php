<?php

namespace App\Filament\Support;

use Filament\Forms\Components\FileUpload;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ImageUploadField
{
    /**
     * Maps each mime type ImageOptimizer actually knows how to re-encode to
     * the extension it's stored under. Anything else is rejected in
     * saveUploadedFileUsing rather than trusted from the client's filename,
     * since a spoofed/mismatched extension shouldn't decide what a file is
     * saved as — the detected mime type does.
     */
    private const EXTENSION_BY_MIME_TYPE = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];

    /**
     * Builds a FileUpload for a column that stores a plain image URL/path
     * string (not a Filament media/attachment relationship). Existing
     * records were seeded with arbitrary external URLs, not disk-relative
     * paths, so this disables Filament's default "does this path exist on
     * disk" check on hydration — otherwise, opening and saving a record
     * whose image is an external URL would silently blank the field out,
     * since that URL never "exists" on the local disk.
     *
     * On save, a newly uploaded file is re-encoded/resized by ImageOptimizer
     * and its disk-relative path is persisted as-is — not expanded to a full
     * URL here, so the column stays correct if APP_URL changes later (see
     * StoredImageUrl, which resolves it to an absolute URL at read time). An
     * untouched field's existing value (relative path or external URL) just
     * passes through unchanged.
     */
    public static function make(string $name, string $directory): FileUpload
    {
        return FileUpload::make($name)
            ->acceptedFileTypes(ImageOptimizer::SUPPORTED_MIME_TYPES)
            ->disk('public')
            ->directory($directory)
            ->visibility('public')
            ->fetchFileInformation(false)
            ->saveUploadedFileUsing(fn (UploadedFile $file): ?string => self::storeUploadedFile($file, $directory))
            ->getUploadedFileUsing(function (string $file): array {
                return [
                    'name' => basename(parse_url($file, PHP_URL_PATH) ?: $file),
                    'size' => 0,
                    'type' => null,
                    'url' => static::isAbsoluteUrl($file) ? $file : Storage::disk('public')->url($file),
                ];
            })
            ->dehydrateStateUsing(fn (?string $state) => blank($state) ? null : $state);
    }

    protected static function isAbsoluteUrl(string $value): bool
    {
        return (bool) preg_match('#^https?://#i', $value);
    }

    private static function storeUploadedFile(UploadedFile $file, string $directory): ?string
    {
        if (! $file->isValid()) {
            return null;
        }

        $mimeType = $file->getMimeType();
        $extension = self::EXTENSION_BY_MIME_TYPE[$mimeType] ?? null;

        if ($extension === null) {
            return null;
        }

        $path = trim($directory, '/').'/'.Str::ulid().'.'.$extension;

        $contents = ImageOptimizer::optimize(
            file_get_contents($file->getRealPath()),
            $mimeType,
        );

        return Storage::disk('public')->put($path, $contents, 'public') ? $path : null;
    }
}
