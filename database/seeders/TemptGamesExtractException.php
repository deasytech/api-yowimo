<?php

namespace Database\Seeders;

use RuntimeException;

/**
 * Thrown when the extracted Tempt Games JSON file cannot be read or is
 * malformed, from either the seeder or the sync command.
 */
class TemptGamesExtractException extends RuntimeException
{
    public static function notFound(string $path): self
    {
        return new self("Extracted games file not found at: {$path}");
    }

    public static function missingPacksArray(string $path): self
    {
        return new self("Invalid extracted games file at {$path}: missing top-level 'packs' array.");
    }
}
