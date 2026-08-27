# Current Task

AI Host v0 (narrow scope): a single `AIProvider` interface with one concrete OpenAI implementation, one prompt triggered by `RoundCompleted`/`GameCompleted`, delivered as a message into the game's realtime channel.

# Why This Task

Per `docs/implementation/IMPLEMENTATION_ORDER.md` Sprint 13, this is the next item now that Sprint 12 (Analytics & observability baseline) has landed. It's deliberately narrow — the doc's full "Yowi" persona (voice, moderation, translation, recommendations) is explicitly out of scope for this sprint.

# Objectives

- [ ] Add an `App\Services\AI\AIProvider` interface (one method, e.g. `respond(string $prompt): string`, exact signature TBD with the user) + one concrete `OpenAiProvider` implementation. No multi-provider fallback chain, no provider registry — that's premature for v0.
- [ ] Trigger one prompt off `RoundCompleted` and/or `GameCompleted` (both already dispatch fire-after-commit, from Sprint 5/7) — confirm with the user which of the two (or both) should trigger the AI host this sprint, and what the prompt should actually ask for (a recap? a reaction? a taunt/encouragement? — the doc's "Yowi" persona isn't scoped here, so the prompt content/tone needs a decision, not an invention).
- [ ] Deliver the AI response into the game's existing realtime channel (`game-session.{id}`, private, Sprint 8) as a new broadcast event — confirm the event name/shape and whether it needs its own listener class (mirroring the `Send*PushNotification` listener pattern) or can broadcast directly from the event that already fires.
- [ ] Handle the OpenAI call being slow/unreliable: confirm with the user whether this call happens synchronously in a queued listener (consistent with every other Sprint 5+ consumer being `ShouldQueue`) and what happens if OpenAI errors or times out (skip silently vs. retry vs. surface an error state) — do not invent retry/backoff policy without confirming.
- [ ] Confirm which OpenAI model and API key configuration approach to use — no AI/LLM package or credentials are installed today, same "confirm before adding a new external dependency and credentials" rule as Sprint 12's Sentry decision.

# Dependencies

Must already exist before starting (all confirmed present):

- `RoundCompleted`/`GameCompleted` events (`app/Events/`, Sprint 5/7) — this sprint is a consumer of them, not a rework.
- `game-session.{id}` private channel (Sprint 8, `App\Events\RoundCompleted`/`GameCompleted` already broadcast on it) — the AI response rides the same channel.
- Domain-event backbone's queued-listener pattern (Sprint 5+) — precedent for how a new consumer should be wired (`ShouldQueue`, auto-discovered `handle()`).

# Files Likely to Change

New:

- `app/Services/AI/AIProvider.php` (interface), `app/Services/AI/OpenAiProvider.php` (implementation).
- A new listener (e.g. `app/Listeners/SendAiHostMessage.php`) off `RoundCompleted`/`GameCompleted`, following the existing `Send*PushNotification` listener pattern.
- A new broadcast event carrying the AI response into `game-session.{id}`, if the response isn't piggybacked onto an existing event.
- OpenAI config (`config/services.php` entry or a new `config/ai.php`) + `.env.example` entry for the API key, credentials left blank (same "wired but inert" pattern as Sprint 9/12).

Edited:

- Possibly `app/Providers/AppServiceProvider.php`, if `AIProvider` needs an explicit interface binding (mirroring the existing `PaymentProvider` binding).

Explicitly not expected to change:

- Any existing API controller, service, the Filament admin panel, or the analytics/health infrastructure from Sprint 12.
- Any existing event's dispatch call or payload shape; `RoundCompleted`/`GameCompleted` keep firing exactly as they do today.
- The Game Engine's actual game-state logic (scoring, turn advancement) — this sprint only reacts to completion events, it doesn't change what completes them.

# Definition of Done

- An AI-generated message is broadcast into `game-session.{id}` after the confirmed trigger event(s) fire, in at least one environment with a real OpenAI key configured.
- `vendor/bin/pint --dirty --format agent` is clean.
- Full test suite passes (`php artisan test --compact`), including all existing Sprint 1–12 tests unchanged.

# Testing Requirements

- New tests asserting the new listener is queued and, given a faked/mocked `AIProvider`, broadcasts the expected event/payload.
- A test covering the OpenAI-failure path per whatever policy is confirmed with the user (skip/retry/error).
- Full regression: `php artisan test --compact` must remain green.

# If Ambiguous

`IMPLEMENTATION_ORDER.md`'s Sprint 13 entry doesn't specify: the exact `AIProvider` interface signature, which of `RoundCompleted`/`GameCompleted` triggers the prompt (or both), what the prompt should actually ask the model for (the "Yowi" persona/tone isn't scoped), the broadcast event name/shape for the AI response, whether the OpenAI call is synchronous-in-a-queued-listener or needs different handling, the failure/timeout policy, which OpenAI model to target, and how the API key is configured. Confirm these with the user before inventing any of them, per `CLAUDE.md`.
