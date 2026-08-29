<?php

namespace App\Listeners;

use App\Enums\WalletTransactionType;
use App\Events\GameCompleted;
use App\Models\GameSession;
use App\Models\Turn;
use App\Models\User;
use App\Services\Wallet\WalletService;
use Illuminate\Contracts\Queue\ShouldQueue;

class GrantGameCompletionReward implements ShouldQueue
{
    private const REWARD_AMOUNT = 25;

    public function __construct(private readonly WalletService $walletService) {}

    public function handle(GameCompleted $event): void
    {
        $gameSession = GameSession::find($event->gameSessionId);

        if (! $gameSession) {
            return;
        }

        $userIds = Turn::where('game_session_id', $gameSession->id)->distinct()->pluck('user_id');
        $users = User::whereIn('id', $userIds)->get();

        foreach ($users as $user) {
            $this->walletService->credit(
                $user,
                self::REWARD_AMOUNT,
                WalletTransactionType::Reward,
                description: 'Game completion reward',
                idempotencyKey: "game-completion-reward:{$gameSession->id}:{$user->id}",
            );
        }
    }
}
