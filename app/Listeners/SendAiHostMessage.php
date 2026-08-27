<?php

namespace App\Listeners;

use App\Events\AiHostMessageSent;
use App\Events\GameCompleted;
use App\Models\GameSession;
use App\Services\AI\AIProvider;
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
        } catch (Throwable $e) {
            Log::warning('AI host message failed, skipping.', [
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
}
