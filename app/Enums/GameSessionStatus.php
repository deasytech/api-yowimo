<?php

namespace App\Enums;

enum GameSessionStatus: string
{
    case Running = 'running';
    case Completed = 'completed';
}
