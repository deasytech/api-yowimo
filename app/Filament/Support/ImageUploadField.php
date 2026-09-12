<?php

namespace App\Filament\Support;

use Filament\Forms\Components\FileUpload;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ImageUploadField
{
    /**
     * Builds a FileUpload for a column that stores a plain image URL string
     * (not a Filament media/attachment relationship). Existing records were
     * seeded with arbitrary external URLs, not disk-relative paths, so this
     * disables Filament's default "does this path exist on disk" check on
     * hydration — otherwise, opening and saving a record whose image is an
     * external URL would silently blank the field out, since that URL never
     * "exists" on the local disk.
     *
     * On save, a newly uploaded file is re-encoded/resized by ImageOptimizer
     * before it's written to disk, and its resulting relative path is
     * expanded to a full public URL before persisting, so the column always
     * holds a directly-usable, size-optimized URL — matching what API
     * consumers expect.
     */
    public static function make(string $name, string $directory): FileUpload
    {
        return FileUpload::make($name)
            ->image()
            ->disk('public')
            ->directory($directory)
            ->visibility('public')
            ->fetchFileInformation(false)
            ->saveUploadedFileUsing(function (UploadedFile $file) use ($directory): ?string {
                if (! $file->isValid()) {
                    return null;
                }

                $path = trim($directory, '/').'/'.Str::ulid().'.'.($file->getClientOriginalExtension() ?: 'bin');

                $contents = ImageOptimizer::optimize(
                    file_get_contents($file->getRealPath()),
                    $file->getMimeType(),
                );

                if (Storage::disk('public')->put($path, $contents, 'public') === false) {
                    return null;
                }

                return $path;
            })
            ->getUploadedFileUsing(function (string $file): array {
                return [
                    'name' => basename(parse_url($file, PHP_URL_PATH) ?: $file),
                    'size' => 0,
                    'type' => null,
                    'url' => static::isAbsoluteUrl($file) ? $file : Storage::disk('public')->url($file),
                ];
            })
            ->dehydrateStateUsing(function (?string $state) {
                if (blank($state)) {
                    return null;
                }

                return static::isAbsoluteUrl($state) ? $state : Storage::disk('public')->url($state);
            });
    }

    protected static function isAbsoluteUrl(string $value): bool
    {
        return (bool) preg_match('#^https?://#i', $value);
    }
}
