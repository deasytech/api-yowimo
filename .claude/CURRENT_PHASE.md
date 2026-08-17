# Current Phase — Yowimo Backend

**Assessed:** 2026-08-17, against `dev` after Sprint 8 landed, by direct code inspection.
**Basis:** `docs/audit/*`, `docs/implementation/IMPLEMENTATION_ORDER.md`, `.claude/PROJECT_CONTEXT.md`.

---

## Current Sprint

**Sprint 8 — Realtime (Reverb)** (`docs/implementation/IMPLEMENTATION_ORDER.md`), **done, with scope decided with the user up front.** Nothing blocks starting Sprint 9.

- ✅ `laravel/reverb` installed; `config/broadcasting.php`/`config/reverb.php` published; `bootstrap/app.php` wires `routes/channels.php` via `withBroadcasting()` with `['middleware' => ['auth:clerk', 'throttle:api']]` — the framework's default `broadcasting/auth` route registration uses the `web` guard, which this API (Bearer-token-only, no sessions) doesn't use, so this had to be set explicitly, along with a `'guards' => ['clerk']` option on every channel in `routes/channels.php` (same underlying reason: `Broadcast::channel()` closures resolve the user via the *default* guard unless told otherwise).
- ✅ `party.{partyId}` presence channel (party lobby) and `game-session.{gameSessionId}` private channel (active game), both membership-gated via `PartyMember`.
- ✅ Added `App\Events\TurnStarted` (dispatched from `GameSessionService::dealTurn()`, fire-after-commit) — confirmed with the user, since `IMPLEMENTATION_ORDER.md`'s Sprint 8 entry names this event but it was never built in Sprint 6/7.
- ✅ `ShouldBroadcast` added directly on the event classes (confirmed with the user over a separate-listener approach): `PartyMemberJoined`, `PartyStarted` → `party.{id}` presence channel; `TurnStarted`, `RoundCompleted` → `game-session.{id}` private channel; `GameCompleted` → both (game session + party lobby, since it's meaningful to each).
- ⚠️ **Scope call made without asking, worth a second look:** `WalletCredited`/`WalletDebited`/`PurchaseCompleted`/`PartyCreated` were **not** broadcast — they're user-scoped, not party/game-scoped, and don't fit either channel this sprint defines (a private per-user channel would be needed). `IMPLEMENTATION_ORDER.md`'s Sprint 8 text technically lists broadcasting "the events already fired since Sprint 5–7" without qualification, so this narrows that literal reading.
- ⚠️ **Known gap surfaced, not fixed:** `PartyMembershipService::leave()` never dispatches any event (a pre-existing Sprint 5 gap, not introduced here) — so the party presence channel reflects joins via `PartyMemberJoined`, but not leaves via a domain event (a leaving user's own WebSocket disconnect still updates the presence channel's live roster automatically, since that's Reverb's connection-tracking, not our event system).
- ✅ Broadcasting auth is tested via `POST /broadcasting/auth` using the real `reverb` (Pusher-protocol) driver — the `null` driver used by the rest of the test suite (phpunit.xml) no-ops channel auth entirely and can't exercise these closures. Test-only quirk worth knowing: `routes/channels.php` registers channels against whichever broadcaster is default *at boot*, so switching `broadcasting.default` mid-test requires re-`require`-ing that file — see `tests/Feature/Broadcasting/ChannelAuthorizationTest.php`.

Sprint 7 (done previously): server-authoritative 30s turn timer (delayed queue job) with AFK-skip tracked per turn, crash-recovery sweep (`game:sweep-expired-turns`), `RoundCompleted`/`GameCompleted` events. Reward-granting was explicitly descoped by the user and remains unscheduled.

Sprint 6 (done previously): `game_sessions`/`rounds`/`turns` tables + `GameSessionService`; host-only `POST /parties/{id}/game/start` and `POST /game/{id}/next-turn`; randomized turn order, host-configurable rounds, Truth/Dare card dealing with no-repeat-until-exhausted, auto-completion.

Sprint 5 (done previously): `app/Events`/`app/Listeners` backbone; `PartyCreated`, `PartyMemberJoined`, `PartyStarted`, `WalletCredited`, `WalletDebited`, `PurchaseCompleted` all dispatch fire-after-commit; one queued listener (`RecordAnalyticsEvent`) proven against the real Horizon queue.

Sprint 4 (done previously): `party_members` table + `PartyMembershipService`; join/leave/start/end lifecycle; `players_count` wired to real membership counts.

Sprint 3 (done previously): `pack_purchases` table + `PackPurchaseService`, `POST /packs/{id}/purchase` debiting the wallet, race-guarded, ownership-gated `PackCard` content.

Sprint 2 (done previously): `PurchaseService` + `PaymentProvider`/`ManualPaymentProvider`, `POST /token-bundles/{id}/purchase` crediting the wallet, idempotency-key enforced.

Outstanding from Sprint 1 (not blocking, can land anytime):
- ⬜ `clerk:sync-users` is not scheduled anywhere.
- ⬜ No CI workflow enforces Pint/Pest on PRs.

Everything else shipped so far predates the sprint roadmap — it's the Phase-0/Phase-1 foundation (Clerk auth, catalog, party create/discover/like, the wallet ledger engine) that the roadmap was written to build on top of.

---

## Completed Modules

Built, exposed via API, and tested:

| Module | Notes |
|---|---|
| **Authentication (Clerk)** | JWT verification, JWKS caching, JIT user provisioning, webhook sync, backfill command. Fully tested, no known gaps. |
| **Game/Pack Catalog** | `GameType`, `Pack`, `PackCard` — full read API, filtering, search, cursor pagination, featured packs, preview cards. |
| **Party (create/discover/like/membership/lifecycle)** | Create, discover, show, room codes, like/unlike, join/leave, host-only start/end — the full party can now actually be played end to end. |
| **Wallet (read API)** | `GET /wallet`, `GET /wallet/transactions` (cursor-paginated) over the existing `WalletService` ledger, `WalletPolicy`-guarded. |
| **Token Bundle purchase (top-up)** | `POST /token-bundles/{id}/purchase` — `PurchaseService` + manual/test `PaymentProvider` driver, credits via `WalletService::credit()`, `Idempotency-Key` enforced. No real payment gateway yet. |
| **Pack purchase & inventory** | `POST /packs/{id}/purchase` — `PackPurchaseService`, debits via `WalletService::debit()`, race-guarded, `Idempotency-Key` enforced, gates full `PackCard` content behind ownership. |
| **Domain events & listeners backbone** | `app/Events`/`app/Listeners`; six events dispatch fire-after-commit from Wallet/Party/PartyMembership/Purchase services; `RecordAnalyticsEvent` proven end-to-end on the Horizon queue. |

---

## Partially Complete Modules

Real code exists but the module is narrower than its documented scope, or is unreachable:

| Module | What's done | What's missing |
|---|---|---|
| **Wallet** | `WalletService` (unmodified) + a read-only `GET /wallet` / `GET /wallet/transactions` API, `WalletPolicy`, `UserResource` now reports real balance/currency. | No direct wallet write endpoint (top-up happens only via token bundle purchase below). |
| **User Profile** | View/edit own profile. | No public profile view, no avatar upload, no account deletion. |
| **Token Bundles** | List (catalog) + `POST /token-bundles/{id}/purchase` (credits wallet, idempotent). | No `show` endpoint. Purchase uses a manual/test `PaymentProvider`, not a real payment gateway. |
| **Packs (catalog + purchase)** | List/discover/show, `POST /packs/{id}/purchase` (debits wallet, grants ownership, full content unlocked). | Nothing planned — scope complete for the current roadmap. |
| **Sponsorship** | `parties.is_sponsored` / `sponsor_name` columns exist. | No sponsor entity, no sponsor-facing flow of any kind — schema hint only. |
| **Game Engine (rounds/turns/timers)** | `game_sessions`/`rounds`/`turns` tables + `GameSessionService`; host-only start/next-turn, randomized turn order, host-configurable rounds, Truth/Dare card dealing with no-repeat-until-exhausted, auto-completion; 30s server-authoritative turn timer with AFK-skip (tracked per turn), crash-recovery sweep, and `RoundCompleted`/`GameCompleted` events. | Votes, scoring, and reward granting — none of these were built; rewards were explicitly descoped from Sprint 7 by the user and have no owning sprint in the current plan (see Current Priority). |
| **Realtime (Reverb)** | `laravel/reverb` installed; `party.{id}` presence channel + `game-session.{id}` private channel, both membership-gated; `PartyMemberJoined`/`PartyStarted`/`TurnStarted`/`RoundCompleted`/`GameCompleted` broadcast (new `TurnStarted` event added this sprint). | Wallet/Purchase/`PartyCreated` events aren't broadcast (no per-user channel exists yet — deliberately out of this sprint's two-channel scope); `PartyMembershipService::leave()` doesn't dispatch any event to broadcast (pre-existing Sprint 5 gap); no live client (React Native) has verified the integration end-to-end. |

---

## Missing Modules

No migration, model, route, or config exists for any of these:

Notifications, Chat/Messaging, Friends/social graph, AI Host, Voice/Video (LiveKit), Admin Panel, Moderation/Trust & Safety, Analytics/Observability infrastructure, Creator Economy, Corporate/Multi-Tenant/Enterprise, Internationalization, CI/CD pipeline.

(Marketplace purchase flow/inventory/ownership moved to Partially Complete above — token bundle and pack purchase both now exist; only a real payment gateway is missing.)

---

## Current Priority

Start **Sprint 9 — Notifications v0** (`docs/implementation/IMPLEMENTATION_ORDER.md`):

1. Device/push-token registration table; FCM integration; `Notification` classes hooked to existing Listeners (e.g. notify on `PartyMemberJoined`, `RoundCompleted`, `WalletCredited`).
2. Runs on the queue activated in Sprint 5.
3. **Risk:** low-medium — purely additive consumer of already-correct events; failure mode is "notification doesn't send," not "game state is wrong."

Outstanding, unscheduled (needs a design decision before it can be assigned to a sprint):
- Reward granting on round/game completion (amount, trigger, recipients) — explicitly out of Sprint 7 per the user; no sprint in the current 14-sprint plan owns it.
- Broadcasting Wallet/Purchase events and `PartyMemberLeft` — surfaced by Sprint 8; would need a per-user private channel and (for leave) a new domain event that doesn't exist yet. Not scheduled; flag if Sprint 9 (Notifications) ends up wanting the same per-user channel infrastructure.

Lower-priority, not blocking, carried over from Sprint 1:
- Schedule `clerk:sync-users` as an hourly self-heal job.
- Add a GitHub Actions workflow running Pint + Pest on every PR.

---

## Next Recommended Sprint

**Sprint 10 — Friends / social graph**, once Sprint 9 lands: `friends`/`friend_requests` tables, request/accept/reject endpoints. Independent of the game loop; flagged as parallelizable earlier if a second engineer is available. Risk: low — new, isolated domain with no dependency on money or game state.

---

## Overall Completion Percentage

A single number is misleading given the scope gap between the documented vision and the current build target — three reference points instead:

| Reference frame | Completion | Basis |
|---|---|---|
| **Pre-roadmap foundation** (Clerk auth, catalog, party create/like, wallet engine) | **~100%** of its own scope | This slice is finished, tested, and stable — no further work planned against it except the Sprint 1 exposure fix. |
| **`docs/implementation/IMPLEMENTATION_ORDER.md`** (14-sprint actionable plan to a complete, playable core product) | **8 of 14 sprints executed (~57%)** | Sprints 1–8 done (Sprint 7 minus reward-granting, descoped per the user); Sprint 9 (Notifications) is next. |
| **Full documented platform vision** (`docs/architecture/`, ~26 modules incl. Marketplace, Realtime, AI, Admin, Enterprise, Creator Economy) | **~25%** | 6 of ~26 modules fully built+exposed (Auth, Game Catalog, Party Likes, Wallet, Marketplace-purchase, Domain Events), 6 partial (incl. Game Engine and the new Realtime), ~14 with zero code. Weighted toward "exists and works," not toward doc page count. |

For context: `docs/architecture/60_PLATFORM_ROADMAP.md` claims "Phase 1: Foundation" is `Status: Completed` including Friends, Marketplace, Notifications, Realtime, and Voice — that claim does not hold against the code (see `docs/audit/ARCHITECTURE_GAP_ANALYSIS.md`). The 15% figure above is the code-verified number; treat any completion claim inside `docs/architecture/` as aspirational framing, not status.
