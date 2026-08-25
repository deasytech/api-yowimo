# Implementation Progress — Yowimo Backend

**Assessed:** 2026-08-23, after Sprint 9 (Notifications v0) landed, by direct code inspection.
**Sources:** `docs/audit/*`, `docs/implementation/IMPLEMENTATION_ORDER.md`, `.claude/CURRENT_PHASE.md`, `.claude/IMPLEMENTATION_STATUS.md`.

"Blocked" below means zero code exists **and** an upstream dependency isn't finished yet. "Not Started" means zero code exists but nothing is stopping work from beginning today.

---

## Completed modules

Built, exposed, tested — no further work planned:

- **Authentication (Clerk)** — JWT verification, JIT provisioning, webhook sync, backfill command.
- **Game/Pack/PackCard Catalog** (read) — full filtering/search/pagination.
- **Party Likes** — idempotent like/unlike.
- **Wallet (read API)** — `GET /wallet`, `GET /wallet/transactions` over the existing `WalletService` ledger; `UserResource` stub replaced with real balance/currency.
- **Token Bundle purchase (top-up)** — `POST /token-bundles/{id}/purchase` credits the wallet via `PurchaseService` + a manual/test `PaymentProvider` driver, idempotency-key enforced.
- **Pack purchase & inventory** — `POST /packs/{id}/purchase` debits the wallet via `PackPurchaseService` (race-guarded), records ownership in `pack_purchases`, and gates full (non-preview) `PackCard` content behind ownership.
- **Party membership & lifecycle** — `party_members` table + `PartyMembershipService`; `POST/DELETE /parties/{id}/join,leave`, host-only `POST /parties/{id}/start,end`; `players_count` wired to real membership counts.
- **Domain events & listeners backbone** — `app/Events`/`app/Listeners`; `PartyCreated`, `PartyMemberJoined`, `PartyStarted`, `WalletCredited`, `WalletDebited`, `PurchaseCompleted` all dispatch fire-after-commit; `RecordAnalyticsEvent` proven end-to-end on the Horizon queue.
- **Game Engine timers & completion events** — 30s server-authoritative turn timer (delayed queue job, `afterCommit()`), AFK-skip tracked per turn, crash-recovery sweep (`game:sweep-expired-turns`, scheduled every minute), `RoundCompleted`/`GameCompleted` events. Reward granting was explicitly descoped from this sprint per the user — see In progress below.
- **Push token registration** — `POST`/`DELETE /push-tokens` over `PushTokenService`; one token per user, replace-on-register.

## In progress modules

Real code exists; work remains to finish the module:

| Module | Done | Remaining |
|---|---|---|
| **User Profile** | View/edit own profile. | Public profile view, avatar upload, account deletion. |
| **Token Bundles** | Catalog list + purchase (top-up). | `show` endpoint; a real payment provider (currently manual/test only). |
| **Packs** | Catalog + purchase + ownership-gated full content. | Nothing planned — scope complete for now. |
| **Horizon / Queue** | Installed, configured, and active since Sprint 5 (`RecordAnalyticsEvent` proven end-to-end, also driving the Sprint 7 turn-timer job and, now, notification delivery). | Gate still needs to extend past `local` (Sprint 11, once an admin concept exists). |
| **Sponsorship** | `is_sponsored`/`sponsor_name` columns exist on `parties`. | Everything else — no sponsor entity or flow. |
| **Game Engine (rounds/turns/timers)** | `game_sessions`/`rounds`/`turns` tables + `GameSessionService`; host-only start/next-turn, randomized turn order, host-configurable rounds, Truth/Dare card dealing, auto-completion, 30s turn timer + AFK handling, `RoundCompleted`/`GameCompleted` events. | Votes, scoring, reward granting — unscheduled; rewards were explicitly dropped from Sprint 7 and have no owning sprint in `IMPLEMENTATION_ORDER.md`. |
| **Realtime (Reverb)** | `laravel/reverb` installed; `party.{id}` presence channel + `game-session.{id}` private channel; `PartyMemberJoined`/`PartyStarted`/`TurnStarted`/`RoundCompleted`/`GameCompleted` broadcast. | Wallet/Purchase events and `PartyCreated` aren't broadcast (no per-user channel yet); `PartyMembershipService::leave()` still doesn't dispatch any event; no live client integration verified. |
| **Notifications** | `push_tokens` table/API; `kreait/laravel-firebase` FCM channel (lazily resolved — safe when no token/no Firebase config); `PartyMemberJoinedNotification`/`RoundCompletedNotification`/`WalletCreditedNotification`, each queued off a new listener. | No real Firebase project/credentials configured in any environment (push is wired but inert until `FIREBASE_CREDENTIALS` is set); only 3 of 9 fired events notify; no in-app delivery; no client (React Native) has verified receiving a real push. |

## Blocked modules

Zero code, and an upstream dependency must land first:

| Module | Blocked on |
|---|---|
| **AI Host** | Realtime (in progress, not blocking — enough exists to start) |

## Not started modules

Zero code, nothing blocking — ready to schedule:

- CI/CD Pipeline
- Friends / Social Graph — next up, Sprint 10
- Admin Panel

**Deferred** (zero code, intentionally not scheduled pending a business trigger — see `IMPLEMENTATION_ORDER.md` §G): Chat/Messaging, Voice/Video (LiveKit), Moderation/Trust & Safety, Creator Economy, Corporate/Multi-Tenant/Enterprise, Internationalization.

---

## Estimated completion

Illustrative only — assumes ~1 engineer-week per sprint per `IMPLEMENTATION_ORDER.md`, starting now (2026-07-13). Adjust to actual team capacity.

| Sprint | Module(s) | Est. week of |
|---|---|---|
| 1 | Wallet API exposure, CI | 2026-07-13 |
| 2–3 | Token + pack purchase (Marketplace v0) | 2026-07-20 – 2026-07-27 |
| 4 | Party membership/lifecycle | 2026-08-03 |
| 5 | Domain events + queue activation | 2026-08-10 |
| 6–7 | Game Engine (rounds/turns, then timers/scoring) | 2026-08-17 – 2026-08-24 |
| 8 | Realtime (Reverb) | 2026-08-31 |
| 9 | Notifications | 2026-08-23 |
| 10 | Friends | 2026-09-07 |
| 11–12 | Admin panel, Analytics baseline | 2026-09-21 – 2026-09-28 |
| 13 | AI Host v0 | 2026-10-05 |
| 14 | Hardening pass | 2026-10-12 |

**~14 weeks (one engineer-quarter) to a complete, playable, monetizable core product**, before any Tier-4/deferred scope is considered.

---

## Overall progress

| Reference frame | Progress |
|---|---|
| Pre-roadmap foundation (Auth, Catalog, Party create/like, Wallet engine) | **~100%** of its own scope — done |
| `IMPLEMENTATION_ORDER.md` 14-sprint plan | **9 of 14 sprints complete (~64%)** — Sprint 10 (Friends) is next; reward granting from Sprint 7's original scope is descoped and unscheduled |
| Full documented platform vision (`docs/architecture/`) | **~27%** — 7 modules complete, 6 in progress (incl. Game Engine, Realtime, and the new Notifications), the rest (~13) not started/blocked/deferred |

`docs/architecture/60_PLATFORM_ROADMAP.md` claims Phase 1 is fully complete including Friends, Marketplace, Notifications, Realtime, and Voice — the code does not support that claim (see `docs/audit/ARCHITECTURE_GAP_ANALYSIS.md`). The figures above are the code-verified numbers.
