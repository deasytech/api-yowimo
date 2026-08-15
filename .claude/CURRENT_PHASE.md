# Current Phase — Yowimo Backend

**Assessed:** 2026-08-15, against `dev` after Sprint 6 landed, by direct code inspection.
**Basis:** `docs/audit/*`, `docs/implementation/IMPLEMENTATION_ORDER.md`, `.claude/PROJECT_CONTEXT.md`.

---

## Current Sprint

**Sprint 6 — Game Engine: rounds & turns (data + state machine)** (`docs/implementation/IMPLEMENTATION_ORDER.md`), **done.** Nothing blocks starting Sprint 7.

- ✅ `game_sessions`, `rounds`, `turns` tables + `GameSessionService`.
- ✅ Host-only `POST /parties/{id}/game/start` (requires `Live` party status) creates a session, randomizes turn order once, deals the first card from the party's `Pack`.
- ✅ Host may configure `rounds` (5/10/15/20, default 10) on start.
- ✅ Host-only `POST /game/{id}/next-turn` completes the current turn, deals the next one (alternating Truth/Dare, reshuffling once a kind is exhausted), advances rounds, and auto-completes the session after the last turn of the last round.
- ✅ Scoped to `PackCardKind` (Truth/Dare) only, per plan — no timers, votes, scoring, or rewards yet (Sprint 7).
- ✅ No domain events fired for game-session state changes yet — `RoundCompleted`/`GameCompleted` are explicitly Sprint 7's job (bundled with reward granting per `IMPLEMENTATION_ORDER.md`); adding them now would be inventing scope ahead of the plan.

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
| **Game Engine (rounds/turns)** | `game_sessions`/`rounds`/`turns` tables + `GameSessionService`; host-only start/next-turn, randomized turn order, host-configurable rounds, Truth/Dare card dealing with no-repeat-until-exhausted, auto-completion. | Timers, AFK handling, votes, scoring, rewards, and the `RoundCompleted`/`GameCompleted` events — all Sprint 7. |

---

## Missing Modules

No migration, model, route, or config exists for any of these:

Realtime (Reverb), Notifications, Chat/Messaging, Friends/social graph, AI Host, Voice/Video (LiveKit), Admin Panel, Moderation/Trust & Safety, Analytics/Observability infrastructure, Creator Economy, Corporate/Multi-Tenant/Enterprise, Internationalization, CI/CD pipeline.

(Marketplace purchase flow/inventory/ownership moved to Partially Complete above — token bundle and pack purchase both now exist; only a real payment gateway is missing.)

---

## Current Priority

Start **Sprint 7 — Game Engine: timers, scoring, completion** (`docs/implementation/IMPLEMENTATION_ORDER.md`):

1. Server-authoritative turn timer (scheduled tick or queued delayed job — safe to build now that Sprint 5 activated the queue), AFK handling.
2. On round/game completion, grant rewards via the existing `WalletService::credit()` path and fire `RoundCompleted`/`GameCompleted` events (reuses the Sprint 5 backbone) — these were deliberately deferred out of Sprint 6.
3. **Risk:** medium — timer correctness under server restarts/worker crashes needs explicit test coverage (resume behavior), since this is the first time-sensitive logic in the codebase; confirm the exact timer mechanism and AFK/skip rules with the user before starting, since `IMPLEMENTATION_ORDER.md` doesn't fully specify them.

Lower-priority, not blocking, carried over from Sprint 1:
- Schedule `clerk:sync-users` as an hourly self-heal job.
- Add a GitHub Actions workflow running Pint + Pest on every PR.

---

## Next Recommended Sprint

**Sprint 8 — Realtime (Reverb)**, once Sprint 7 lands: install `laravel/reverb`, add `config/broadcasting.php`, define a presence channel for the party lobby and a private channel for an active game session; broadcast the events fired since Sprint 5–7 (`PartyMemberJoined`, `TurnStarted`, `RoundCompleted`, etc.). Should not need to touch game-logic code — transport-only risk.

---

## Overall Completion Percentage

A single number is misleading given the scope gap between the documented vision and the current build target — three reference points instead:

| Reference frame | Completion | Basis |
|---|---|---|
| **Pre-roadmap foundation** (Clerk auth, catalog, party create/like, wallet engine) | **~100%** of its own scope | This slice is finished, tested, and stable — no further work planned against it except the Sprint 1 exposure fix. |
| **`docs/implementation/IMPLEMENTATION_ORDER.md`** (14-sprint actionable plan to a complete, playable core product) | **6 of 14 sprints executed (~43%)** | Sprints 1–6 done; Sprint 7 (Game Engine: timers/scoring) is next. |
| **Full documented platform vision** (`docs/architecture/`, ~26 modules incl. Marketplace, Realtime, AI, Admin, Enterprise, Creator Economy) | **~23%** | 6 of ~26 modules fully built+exposed (Auth, Game Catalog, Party Likes, Wallet, Marketplace-purchase, Domain Events), 5 partial (incl. Game Engine), ~15 with zero code. Weighted toward "exists and works," not toward doc page count. |

For context: `docs/architecture/60_PLATFORM_ROADMAP.md` claims "Phase 1: Foundation" is `Status: Completed` including Friends, Marketplace, Notifications, Realtime, and Voice — that claim does not hold against the code (see `docs/audit/ARCHITECTURE_GAP_ANALYSIS.md`). The 15% figure above is the code-verified number; treat any completion claim inside `docs/architecture/` as aspirational framing, not status.
