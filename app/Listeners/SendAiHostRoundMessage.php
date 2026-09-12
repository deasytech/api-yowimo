<?php

namespace App\Listeners;

use App\Events\AiHostMessageSent;
use App\Events\RoundCompleted;
use App\Models\GameSession;
use App\Services\AI\AIProvider;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;
use Throwable;

class SendAiHostRoundMessage implements ShouldQueue
{
    public function __construct(private readonly AIProvider $provider) {}

    public function handle(RoundCompleted $event): void
    {
        $session = GameSession::with(['party', 'pack'])->find($event->gameSessionId);

        if (! $session) {
            return;
        }

        $prompt = sprintf(
            'You are Yowi, the witty AI host of a party game app. Round %d of %d in the game "%s" just wrapped up in the party "%s". '.
            'Write one short, playful, upbeat reaction (max 2 sentences, no hashtags) hyping up the group for the next round.',
            $event->roundNumber,
            $session->rounds_count,
            $session->pack?->name ?? 'the game',
            $session->party?->title ?? 'the party',
        );

        $message = $this->provider->respond($prompt);

        if ($message === '') {
            return;
        }

        AiHostMessageSent::dispatch($session->id, $message);
    }

    /**
     * The dispatcher reads retry count/backoff off these methods (not a
     * $tries property, which only applies to Job classes) via
     * Illuminate\Events\Dispatcher::propagateListenerOptions().
     */
    public function tries(): int
    {
        return 4;
    }

    /**
     * One initial attempt plus 3 retries, delayed 5s/15s/30s.
     *
     * @return array<int, int>
     */
    public function backoff(): array
    {
        return [5, 15, 30];
    }

    public function failed(RoundCompleted $event, Throwable $exception): void
    {
        Log::warning('AI host round message failed after retries, skipping.', [
            'game_session_id' => $event->gameSessionId,
            'round_id' => $event->roundId,
            'error' => $exception->getMessage(),
        ]);
    }
}
