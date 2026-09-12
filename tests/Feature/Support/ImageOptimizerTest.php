<?php

use App\Filament\Support\ImageOptimizer;

function makeJpeg(int $width, int $height): string
{
    $image = imagecreatetruecolor($width, $height);
    imagefill($image, 0, 0, imagecolorallocate($image, 200, 50, 50));

    ob_start();
    imagejpeg($image, null, 100);
    $contents = ob_get_clean();
    imagedestroy($image);

    return $contents;
}

function makeTransparentPng(int $width, int $height): string
{
    $image = imagecreatetruecolor($width, $height);
    imagealphablending($image, false);
    imagesavealpha($image, true);
    imagefill($image, 0, 0, imagecolorallocatealpha($image, 0, 0, 0, 127));
    imagefilledellipse($image, (int) ($width / 2), (int) ($height / 2), (int) ($width / 2), (int) ($height / 2), imagecolorallocatealpha($image, 10, 200, 10, 0));

    ob_start();
    imagepng($image);
    $contents = ob_get_clean();
    imagedestroy($image);

    return $contents;
}

it('downscales an oversized jpeg to fit within the max dimension', function () {
    $original = makeJpeg(3000, 2000);

    $optimized = ImageOptimizer::optimize($original, 'image/jpeg');

    [$width, $height] = getimagesizefromstring($optimized);

    expect(max($width, $height))->toBe(ImageOptimizer::MAX_DIMENSION)
        ->and($width / $height)->toEqualWithDelta(3000 / 2000, 0.01);
});

it('does not upscale an image already within the max dimension', function () {
    $original = makeJpeg(400, 300);

    $optimized = ImageOptimizer::optimize($original, 'image/jpeg');

    [$width, $height] = getimagesizefromstring($optimized);

    expect([$width, $height])->toBe([400, 300]);
});

it('shrinks file size when re-encoding a large, maximum-quality jpeg', function () {
    $original = makeJpeg(3000, 2000);

    $optimized = ImageOptimizer::optimize($original, 'image/jpeg');

    expect(strlen($optimized))->toBeLessThan(strlen($original));
});

it('preserves png transparency after resizing', function () {
    $original = makeTransparentPng(2000, 2000);

    $optimized = ImageOptimizer::optimize($original, 'image/png');

    $image = imagecreatefromstring($optimized);
    $corner = imagecolorat($image, 2, 2);
    $colors = imagecolorsforindex($image, $corner);
    imagedestroy($image);

    expect($colors['alpha'])->toBe(127);
});

it('returns unsupported formats unchanged', function () {
    $original = 'not-really-a-gif-but-stands-in-for-one';

    expect(ImageOptimizer::optimize($original, 'image/gif'))->toBe($original);
});

it('returns unreadable image bytes unchanged instead of throwing', function () {
    $garbage = 'this is not image data';

    expect(ImageOptimizer::optimize($garbage, 'image/jpeg'))->toBe($garbage);
});
