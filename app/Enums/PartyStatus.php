<?php

namespace App\Enums;

enum PartyStatus: string
{
    case Draft = 'draft';
    case Scheduled = 'scheduled';
    case Live = 'live';
    case Ended = 'ended';
    case Cancelled = 'cancelled';

    /**
     * Statuses that are visible to the public discover feed.
     *
     * @return array<int, self>
     */
    public static function publiclyVisible(): array
    {
        return [self::Scheduled, self::Live];
    }
}
