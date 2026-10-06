<?php

namespace App\Enums;

enum AdRewardSessionStatus: string
{
    case Pending = 'pending';
    case Credited = 'credited';
    case Expired = 'expired';
}
