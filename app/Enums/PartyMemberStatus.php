<?php

namespace App\Enums;

enum PartyMemberStatus: string
{
    case Active = 'active';
    case Left = 'left';
    case Removed = 'removed';
}
