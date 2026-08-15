# Implementation Progress — Yowimo Backend

**Assessed:** 2026-08-15, after Sprint 6 (Game Engine: rounds & turns) landed, by direct code inspection.
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

## In progress modules

Real code exists; work remains to finish the module:

| Module | Done | Remaining |
|---|---|---|
| **User Profile** | View/edit own profile. | Public profile view, avatar upload, account deletion. |
| **Token Bundles** | Catalog list + purchase (top-up). | `show` endpoint; a real payment provider (currently manual/test only). |
| **Packs** | Catalog + purchase + ownership-gated full content. | Nothing planned — scope complete for now. |
| **Horizon / Queue** | Installed, configured, and active since Sprint 5 (`RecordAnalyticsEvent` proven end-to-end). | Gate still needs to extend past `local` (Sprint 11, once an admin concept exists). |
| **Sponsorship** | `is_sponsored`/`sponsor_name` columns exist on `parties`. | Everything else — no sponsor entity or flow. |
| **Game Engine (rounds/turns)** | `game_sessions`/`rounds`/`turns` tables + `GameSessionService`; host-only start/next-turn, randomized turn order, host-configurable rounds, Truth/Dare card dealing, auto-completion. | Timers, AFK handling, votes, scoring, rewards, `RoundCompleted`/`GameCompleted` events — all Sprint 7. |

## Blocked modules

Zero code, and an upstream dependency must land first:

| Module | Blocked on |
|---|---|
| **Realtime (Reverb)** | Game Engine (Sprint 7, in progress) |
| **AI Host** | Realtime |

## Not started modules

Zero code, nothing blocking — ready to schedule:

- CI/CD Pipeline
- Notifications
- Friends / Social Graph
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
| 9–10 | Notifications, Friends | 2026-09-07 – 2026-09-14 |
| 11–12 | Admin panel, Analytics baseline | 2026-09-21 – 2026-09-28 |
| 13 | AI Host v0 | 2026-10-05 |
| 14 | Hardening pass | 2026-10-12 |

**~14 weeks (one engineer-quarter) to a complete, playable, monetizable core product**, before any Tier-4/deferred scope is considered.

---

## Overall progress

| Reference frame | Progress |
|---|---|
| Pre-roadmap foundation (Auth, Catalog, Party create/like, Wallet engine) | **~100%** of its own scope — done |
| `IMPLEMENTATION_ORDER.md` 14-sprint plan | **6 of 14 sprints complete (~43%)** — Sprint 7 (Game Engine: timers/scoring) is next |
| Full documented platform vision (`docs/architecture/`) | **~23%** — 6 modules complete, 5 in progress (incl. Game Engine), the rest (~15) not started/blocked/deferred |

`docs/architecture/60_PLATFORM_ROADMAP.md` claims Phase 1 is fully complete including Friends, Marketplace, Notifications, Realtime, and Voice — the code does not support that claim (see `docs/audit/ARCHITECTURE_GAP_ANALYSIS.md`). The figures above are the code-verified numbers.
