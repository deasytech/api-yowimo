# Current Task

Realtime (Reverb): install Laravel Reverb, add a presence channel for the party lobby and a private channel for an active game session, and broadcast the domain events already firing since Sprint 5–7.

# Why This Task

Per `docs/implementation/IMPLEMENTATION_ORDER.md` Sprint 8, this is the next item now that Sprint 7 (Game Engine: timers, AFK handling, completion events) has landed. The plan flags this sprint's risk as "medium, but contained" — the domain logic being broadcast was already built and tested REST-first (Sprints 4–7), so a realtime bug here is isolated to the transport layer, not the game rules. This sprint should not need to touch game-logic code at all, only add broadcasting listeners on top of the existing `app/Events` backbone.

# Objectives

- [ ] Install `laravel/reverb` and add `config/broadcasting.php`.
- [ ] Define a presence channel for the party lobby (who's currently in the party) and a private channel for an active game session.
- [ ] Broadcast the events that already exist and fire fire-after-commit: `PartyCreated`, `PartyMemberJoined`, `PartyStarted`, `WalletCredited`, `WalletDebited`, `PurchaseCompleted`, `RoundCompleted`, `GameCompleted` — via `ShouldBroadcast`/broadcasting listeners, not by changing what the events carry or when they fire.
- [ ] Do not change any existing event's constructor/payload, and do not change `GameSessionService`, `WalletService`, `PartyService`, `PartyMembershipService`, or `PurchaseService` — this sprint is transport-only.
- [ ] Do not add scoring, votes, or reward-granting — those remain unscheduled (see "If Ambiguous").

# Dependencies

Must already exist before starting (all confirmed present):

- `app/Events/*` — `PartyCreated`, `PartyMemberJoined`, `PartyStarted`, `WalletCredited`, `WalletDebited`, `PurchaseCompleted`, `RoundCompleted`, `GameCompleted`, all dispatching fire-after-commit. Unmodified by this task.
- `app/Models/Party.php`, `app/Models/GameSession.php` — the entities the presence/private channels authorize against.
- Horizon/queue — active since Sprint 5, needed for queued broadcast jobs.

# Files Likely to Change

New:

- `config/broadcasting.php`, Reverb env vars.
- `routes/channels.php` — presence channel for the party lobby, private channel for a game session.
- Broadcasting listeners (or `ShouldBroadcast` on the existing events, per the approach confirmed with the user — see "If Ambiguous").
- `tests/Feature/*` covering channel authorization and that the right events broadcast on the right channel.

Edited:

- Possibly `composer.json`/`config/app.php` (Reverb service provider registration, if not auto-discovered).

Explicitly not expected to change:

- Any existing event's public properties/payload shape.
- `app/Services/Game/GameSessionService.php`, `app/Services/Wallet/WalletService.php`, `app/Services/Parties/*`, `app/Services/Purchase/*`.
- Any existing migration, model, or REST route/controller — this sprint adds a transport layer alongside the existing poll-driven API, it doesn't replace it.

# Definition of Done

- A party lobby's presence channel reflects real membership (join/leave) to a connected client.
- A game session's private channel receives `RoundCompleted`/`GameCompleted` (and the other broadcast events) when they fire.
- Channel authorization is enforced (only party members/the host can subscribe to their party's/session's channels).
- `vendor/bin/pint --dirty --format agent` is clean.
- Full test suite passes (`php artisan test --compact`), including all existing Sprint 1–7 tests unchanged.

# Testing Requirements

- New tests covering: channel authorization (member can subscribe, non-member is rejected), and that broadcasting an event doesn't alter its existing `Event::fake()`-verified dispatch behavior from `tests/Feature/Events/EventDispatchTest.php`.
- Full regression: `php artisan test --compact` must remain green.

# If Ambiguous

`IMPLEMENTATION_ORDER.md`'s Sprint 8 entry names a `TurnStarted` event as something to broadcast — that event doesn't exist; Sprint 6 and Sprint 7 never needed it (turn-dealing is currently only visible via the `POST /game/{id}/next-turn` response and `GameSessionResource.current_turn`). Confirm with the user before starting: (1) whether to add a `TurnStarted` event now so a turn-deal can be broadcast in real time, or defer that and broadcast only the events that already exist; (2) whether to implement broadcasting as `ShouldBroadcast` directly on the existing event classes, or as separate broadcasting listeners — `IMPLEMENTATION_ORDER.md` doesn't specify either. Also carried over, unscheduled: reward granting on round/game completion was explicitly dropped from Sprint 7 by the user and has no owning sprint in the plan — flag it if the user wants it slotted in before or after Realtime.
