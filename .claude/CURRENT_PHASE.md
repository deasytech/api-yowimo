# Current Phase — Yowimo Backend

**Assessed:** 2026-08-16, against `dev` after Sprint 7 landed, by direct code inspection.
**Basis:** `docs/audit/*`, `docs/implementation/IMPLEMENTATION_ORDER.md`, `.claude/PROJECT_CONTEXT.md`.

---

## Current Sprint

**Sprint 7 — Game Engine: timers, AFK handling, completion events** (`docs/implementation/IMPLEMENTATION_ORDER.md`), **done, with one scope change confirmed with the user.** Nothing blocks starting Sprint 8.

- ✅ Server-authoritative 30s turn timer: `GameSessionService::dealTurn()` dispatches a delayed `App\Jobs\SkipAfkTurn` job (`afterCommit()`), which AFK-skips the turn if it's still open when the job runs.
- ✅ AFK skip is tracked per turn (`turns.is_afk`), not just silently advanced — available for future scoring/kick logic.
- ✅ Crash/restart resume: `game:sweep-expired-turns` (scheduled every minute in `routes/console.php`) re-processes any turn whose timer expired but whose delayed job never ran (e.g. a lost Redis-backed job) — idempotent with the queue path via a `completed_at`/elapsed-time guard in `GameSessionService::skipAfkTurn()`.
- ✅ `RoundCompleted`/`GameCompleted` domain events now fire (reusing the Sprint 5 backbone), from a shared `advance()` extracted out of Sprint 6's `nextTurn()` — no turn-order/card-dealing/round-advancement behavior changed, verified by Sprint 6's tests passing unmodified.
- ⚠️ **Scope change from the original plan:** reward-granting via `WalletService::credit()` was explicitly dropped from this sprint per the user ("no amount is credited to players... during play"). `IMPLEMENTATION_ORDER.md`'s Sprint 7 entry bundles rewards with timers/completion — that bundling is now stale. No sprint in the current 14-sprint plan owns reward granting; it needs a design decision (amount/trigger/recipients) before it's scheduled anywhere.
- ⚠️ `IMPLEMENTATION_ORDER.md`'s Sprint 8 entry also references a `TurnStarted` event for broadcasting — that event was never built (Sprint 6 nor 7 needed it; only `RoundCompleted`/`GameCompleted` exist). Flagged for Sprint 8 planning, not added speculatively here.

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

---

## Missing Modules

No migration, model, route, or config exists for any of these:

Realtime (Reverb), Notifications, Chat/Messaging, Friends/social graph, AI Host, Voice/Video (LiveKit), Admin Panel, Moderation/Trust & Safety, Analytics/Observability infrastructure, Creator Economy, Corporate/Multi-Tenant/Enterprise, Internationalization, CI/CD pipeline.

(Marketplace purchase flow/inventory/ownership moved to Partially Complete above — token bundle and pack purchase both now exist; only a real payment gateway is missing.)

---

## Current Priority

Start **Sprint 8 — Realtime (Reverb)** (`docs/implementation/IMPLEMENTATION_ORDER.md`):

1. Install `laravel/reverb`, add `config/broadcasting.php`, define a presence channel for the party lobby and a private channel for an active game session.
2. Broadcast the events already fired since Sprint 5–7 (`PartyMemberJoined`, `RoundCompleted`, `GameCompleted`, etc.) via broadcasting listeners — should not need to touch game-logic code.
3. **Ambiguity to confirm with the user before starting:** `IMPLEMENTATION_ORDER.md`'s Sprint 8 entry also names a `TurnStarted` event, which doesn't exist — Sprint 6/7 never needed it. Decide whether to add it (and where it should dispatch from) as part of Sprint 8, or broadcast only the events that already exist.
4. **Risk:** medium, but contained — the domain logic being broadcast was already built and tested REST-first, so a realtime bug here is isolated to the transport layer, not the game rules.

Outstanding, unscheduled (surfaced by Sprint 7 — needs a design decision before it can be assigned to a sprint):
- Reward granting on round/game completion (amount, trigger, recipients) — explicitly out of Sprint 7 per the user; no sprint in the current 14-sprint plan owns it.

Lower-priority, not blocking, carried over from Sprint 1:
- Schedule `clerk:sync-users` as an hourly self-heal job.
- Add a GitHub Actions workflow running Pint + Pest on every PR.

---

## Next Recommended Sprint

**Sprint 9 — Notifications v0**, once Sprint 8 lands: device/push-token registration table, FCM integration, `Notification` classes hooked to existing Listeners (e.g. `PartyMemberJoined`, `RoundCompleted`, `WalletCredited`). Runs on the queue activated in Sprint 5. Risk: low-medium — purely additive consumer of already-correct events.

---

## Overall Completion Percentage

A single number is misleading given the scope gap between the documented vision and the current build target — three reference points instead:

| Reference frame | Completion | Basis |
|---|---|---|
| **Pre-roadmap foundation** (Clerk auth, catalog, party create/like, wallet engine) | **~100%** of its own scope | This slice is finished, tested, and stable — no further work planned against it except the Sprint 1 exposure fix. |
| **`docs/implementation/IMPLEMENTATION_ORDER.md`** (14-sprint actionable plan to a complete, playable core product) | **7 of 14 sprints executed (50%)** | Sprints 1–7 done (Sprint 7 minus reward-granting, descoped per the user); Sprint 8 (Realtime) is next. |
| **Full documented platform vision** (`docs/architecture/`, ~26 modules incl. Marketplace, Realtime, AI, Admin, Enterprise, Creator Economy) | **~23%** | 6 of ~26 modules fully built+exposed (Auth, Game Catalog, Party Likes, Wallet, Marketplace-purchase, Domain Events), 5 partial (incl. Game Engine, which gained timers/AFK/events but is still short votes/scoring/rewards), ~15 with zero code. Weighted toward "exists and works," not toward doc page count. |

For context: `docs/architecture/60_PLATFORM_ROADMAP.md` claims "Phase 1: Foundation" is `Status: Completed` including Friends, Marketplace, Notifications, Realtime, and Voice — that claim does not hold against the code (see `docs/audit/ARCHITECTURE_GAP_ANALYSIS.md`). The 15% figure above is the code-verified number; treat any completion claim inside `docs/architecture/` as aspirational framing, not status.
