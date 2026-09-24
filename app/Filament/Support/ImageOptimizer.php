<?php

namespace App\Filament\Support;

use GdImage;

/**
 * Re-encodes an uploaded image at a capped resolution and compression
 * quality before it's written to disk, so a cover image uploaded straight
 * from a phone or DSLR isn't stored (and served to the mobile app) at its
 * original multi-megabyte size. Formats GD can't safely re-encode (animated
 * GIFs, unrecognized types) are returned unchanged rather than risk
 * corrupting them.
 */
class ImageOptimizer
{
    public const MAX_DIMENSION = 1600;

    private const JPEG_QUALITY = 82;

    private const PNG_COMPRESSION = 6;

    private const WEBP_QUALITY = 82;

    public const SUPPORTED_MIME_TYPES = ['image/jpeg', 'image/png', 'image/webp'];

    public static function optimize(string $contents, ?string $mimeType): string
    {
        if (! in_array($mimeType, self::SUPPORTED_MIME_TYPES, true)) {
            return $contents;
        }

        if ($mimeType === 'image/webp' && ! function_exists('imagecreatefromwebp')) {
            return $contents;
        }

        $image = @imagecreatefromstring($contents);

        if (! $image instanceof GdImage) {
            return $contents;
        }

        if ($mimeType === 'image/jpeg') {
            $image = self::correctJpegOrientation($image, $contents);
        }

        $image = self::resizeToFit($image, self::MAX_DIMENSION);

        $optimized = self::encode($image, $mimeType);
        imagedestroy($image);

        return $optimized ?? $contents;
    }

    private static function resizeToFit(GdImage $image, int $maxDimension): GdImage
    {
        $width = imagesx($image);
        $height = imagesy($image);

        if (max($width, $height) <= $maxDimension) {
            return $image;
        }

        $scale = $maxDimension / max($width, $height);
        $newWidth = max(1, (int) round($width * $scale));
        $newHeight = max(1, (int) round($height * $scale));

        $resized = imagecreatetruecolor($newWidth, $newHeight);
        imagealphablending($resized, false);
        imagesavealpha($resized, true);
        imagefilledrectangle($resized, 0, 0, $newWidth, $newHeight, imagecolorallocatealpha($resized, 0, 0, 0, 127));
        imagecopyresampled($resized, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
        imagedestroy($image);

        return $resized;
    }

    private static function encode(GdImage $image, string $mimeType): ?string
    {
        ob_start();

        $ok = match ($mimeType) {
            'image/jpeg' => imagejpeg($image, null, self::JPEG_QUALITY),
            'image/png' => self::encodePng($image),
            'image/webp' => imagewebp($image, null, self::WEBP_QUALITY),
            default => false,
        };

        $contents = ob_get_clean();

        return $ok ? ($contents ?: null) : null;
    }

    private static function encodePng(GdImage $image): bool
    {
        imagealphablending($image, false);
        imagesavealpha($image, true);

        return imagepng($image, null, self::PNG_COMPRESSION);
    }

    private static function correctJpegOrientation(GdImage $image, string $contents): GdImage
    {
        if (! function_exists('exif_read_data')) {
            return $image;
        }

        $tmpPath = tempnam(sys_get_temp_dir(), 'yowimo-exif-');

        try {
            file_put_contents($tmpPath, $contents);
            $exif = @exif_read_data($tmpPath);
        } finally {
            @unlink($tmpPath);
        }

        $orientation = $exif['Orientation'] ?? 1;

        $rotated = match ($orientation) {
            3 => imagerotate($image, 180, 0),
            6 => imagerotate($image, -90, 0),
            8 => imagerotate($image, 90, 0),
            default => null,
        };

        if (! $rotated instanceof GdImage) {
            return $image;
        }

        imagedestroy($image);

        return $rotated;
    }
}
