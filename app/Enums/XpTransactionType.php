<?php

namespace App\Enums;

enum XpTransactionType: string
{
    case TurnWinnerVote = 'turn_winner_vote';
    case TurnFunnyVote = 'turn_funny_vote';
    case TurnCreativityVote = 'turn_creativity_vote';
    case ChallengeCompleted = 'challenge_completed';
    case MvpBonus = 'mvp_bonus';
}
