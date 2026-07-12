<?php

namespace App\Enums;

enum PartyMode: string
{
    case Online = 'online';
    case Hybrid = 'hybrid';
    case InPerson = 'in_person';
}
