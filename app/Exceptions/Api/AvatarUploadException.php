<?php

namespace App\Exceptions\Api;

use RuntimeException;

/**
 * Thrown when an avatar image was submitted but couldn't be stored
 * (unsupported type slipping past request validation, or a disk write
 * failure) — surfaced as an error rather than silently saving the profile
 * update without the avatar the caller asked for.
 */
class AvatarUploadException extends RuntimeException
{
    //
}
