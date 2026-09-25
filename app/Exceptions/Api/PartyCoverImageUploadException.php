<?php

namespace App\Exceptions\Api;

use RuntimeException;

/**
 * Thrown when a party cover image was submitted but couldn't be stored
 * (unsupported type slipping past request validation, or a disk write
 * failure) — surfaced as an error rather than silently creating the party
 * without the image the caller asked for.
 */
class PartyCoverImageUploadException extends RuntimeException
{
    //
}
