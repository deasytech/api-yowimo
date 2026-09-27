<?php

namespace App\Listeners;

use App\Events\AiHostMessageSent;
use App\Events\GameCompleted;
use App\Models\GameSession;
use App\Services\AI\AIProvider;
use App\Services\AI\AiProviderFailedException;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;
use Throwable;

class SendAiHostMessage implements ShouldQueue
{
    public function __construct(private readonly AIProvider $provider) {}

    public function handle(GameCompleted $event): void
    {
        $session = GameSession::with(['party', 'pack'])->find($event->gameSessionId);

        if (! $session) {
            return;
        }

        $prompt = sprintf(
            'You are Yowi, the witty AI host of a party game app. The game "%s" just wrapped up in the party "%s" after %d round(s). '.
            'Write one short, playful, upbeat reaction (max 2 sentences, no hashtags) congratulating the group.',
            $session->pack?->name ?? 'the game',
            $session->party?->title ?? 'the party',
            $session->rounds_count,
        );

        try {
            $message = $this->provider->respond($prompt);
        } catch (AiProviderFailedException $e) {
            if ($e->retryable) {
                throw $e;
            }

            // Terminal failures (missing key, exhausted quota, rejected
            // request) repeat identically on every attempt, so the queue
            // retries/backoff have nothing to fix: skip the message with a
            // single warning and let gameplay carry on.
            Log::warning('AI host message skipped: the AI provider will not accept this request.', [
                'game_session_id' => $session->id,
                'error' => $e->getMessage(),
            ]);

            return;
        }

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

    public function failed(GameCompleted $event, Throwable $exception): void
    {
        Log::warning('AI host message failed after retries, skipping.', [
            'game_session_id' => $event->gameSessionId,
            'error' => $exception->getMessage(),
        ]);
    }
}
