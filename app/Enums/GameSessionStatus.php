<?php

namespace App\Enums;

enum GameSessionStatus: string
{
    case Running = 'running';
    case Paused = 'paused';
    // The last turn has been played; players have a short window to vote on it before the game completes.
    case Voting = 'voting';
    case Completed = 'completed';
    // Stopped before the last turn (the host ended the party mid-game); no completion rewards.
    case Ended = 'ended';
}
