<?php

namespace App\Enums;

enum SponsorshipInviteStatus: string
{
    case Pending = 'pending';
    case Paid = 'paid';
    case Cancelled = 'cancelled';
    case Expired = 'expired';
}
