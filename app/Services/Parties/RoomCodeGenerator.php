<?php

namespace App\Services\Parties;

use App\Models\Party;

class RoomCodeGenerator
{
    /**
     * Uppercase alphanumeric charset, excluding ambiguous characters (0, O, 1, I).
     */
    private const CHARSET = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

    private const LENGTH = 6;

    /**
     * Generate a unique, human-shareable party room code.
     */
    public function generate(): string
    {
        do {
            $code = $this->randomCode();
        } while (Party::withTrashed()->where('room_code', $code)->exists());

        return $code;
    }

    private function randomCode(): string
    {
        $code = '';

        for ($i = 0; $i < self::LENGTH; $i++) {
            $code .= self::CHARSET[random_int(0, strlen(self::CHARSET) - 1)];
        }

        return $code;
    }
}
