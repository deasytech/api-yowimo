<?php

namespace App\Enums;

enum VoteCategory: string
{
    case Winner = 'winner';
    case Funny = 'funny';
    case Creativity = 'creativity';
}
