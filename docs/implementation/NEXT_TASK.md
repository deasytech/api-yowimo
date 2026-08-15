# Current Task

Game Engine: turn timers, AFK handling, and round/game completion — server-authoritative timers on top of the Sprint 6 rounds/turns state machine, plus reward granting and completion events.

# Why This Task

Per `docs/implementation/IMPLEMENTATION_ORDER.md` Sprint 7, this is the next item now that Sprint 6 (Game Engine: rounds & turns) has landed. Sprint 6 deliberately built the state machine REST/poll-driven only — no timers — so game-logic bugs and infrastructure bugs wouldn't be debugged simultaneously. Sprint 7 adds the time-sensitive layer on top of an already-correct, already-tested state machine, and is flagged in the plan as the first time-sensitive logic in the codebase (needs explicit resume-after-crash test coverage).

# Objectives

- [ ] Add a server-authoritative turn timer (scheduled tick or queued delayed job — now safe to build since Sprint 5 activated the Horizon queue).
- [ ] Add AFK/skip handling for a player who doesn't act before their timer expires.
- [ ] On round completion and on game completion, grant rewards via the existing `WalletService::credit()` path (reuses Sprint 1–3 machinery).
- [ ] Fire `RoundCompleted`/`GameCompleted` domain events (reuses the Sprint 5 events/listeners backbone) — these were deliberately left out of Sprint 6's `GameSessionService::nextTurn()`, which only flips `status` to `completed` without firing an event.
- [ ] Do not change Sprint 6's turn-order, card-dealing, or round/session-advancement logic in `app/Services/Game/GameSessionService.php` except to hook in the timer/reward/event additions.
- [ ] Ensure timer state survives a worker restart/crash (resume behavior) — this is the first time-sensitive logic in the codebase, so there's no existing pattern to copy.

# Dependencies

Must already exist before starting (all confirmed present):

- `app/Services/Game/GameSessionService.php`, `app/Models/GameSession.php`, `app/Models/Round.php`, `app/Models/Turn.php` — the Sprint 6 state machine to extend, unmodified except for the additive hooks above.
- `app/Http/Controllers/Api/V1/GameSessionController.php`, `POST /parties/{id}/game/start`, `POST /game/{id}/next-turn` — existing endpoints, not expected to change shape.
- `app/Services/Wallet/WalletService.php` — reward path, unmodified.
- `app/Events`/`app/Listeners` — Sprint 5 backbone, unmodified.
- Horizon/queue — active since Sprint 5.

# Files Likely to Change

New:

- `app/Events/RoundCompleted.php`, `app/Events/GameCompleted.php`.
- A timer mechanism — exact file(s) depend on the chosen approach (confirm with the user; see "If Ambiguous" below).
- `tests/Feature/Services/GameSessionTimerTest.php` or equivalent, including a resume-after-crash case.

Edited:

- `app/Services/Game/GameSessionService.php` — hook in reward granting + event dispatch on round/session completion; add AFK/skip handling to `nextTurn()` or an adjacent method.

Explicitly not expected to change:

- `app/Services/Wallet/WalletService.php`, the wallet ledger, or any migration.
- Sprint 6's turn-order randomization, card-dealing/reshuffle logic, or the `game_sessions`/`rounds`/`turns` schema (unless a timer column is genuinely required, in which case confirm with the user first — CLAUDE.md's migration rule).
- `tests/Feature/Services/GameSessionServiceTest.php`, `tests/Feature/Api/V1/GameSessionControllerTest.php` (Sprint 6's tests must keep passing unmodified).

# Definition of Done

- A turn that isn't acted on within its timer is automatically skipped/AFK-handled without a client request.
- Round and game completion grant the correct reward via `WalletService::credit()` and fire `RoundCompleted`/`GameCompleted`.
- Timer state correctly resumes after a simulated worker restart/crash.
- `vendor/bin/pint --dirty --format agent` is clean.
- Full test suite passes (`php artisan test --compact`), including all existing Sprint 6 Game Engine tests unchanged.

# Testing Requirements

- New tests covering: timer expiry triggers AFK/skip, reward is credited on round/game completion, `RoundCompleted`/`GameCompleted` fire with `Event::fake()`, and a resume-after-crash scenario for the timer mechanism.
- Full regression: `php artisan test --compact` must remain green.

# If Ambiguous

`IMPLEMENTATION_ORDER.md`'s Sprint 7 entry doesn't specify: the exact timer mechanism (scheduled tick vs. delayed job), the timer duration per turn, the precise AFK/skip rule (how many misses before what happens), or the reward amount/formula per round/game. Confirm these with the user before inventing any of them, per `CLAUDE.md`.
