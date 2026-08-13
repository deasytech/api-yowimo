# Implementation Progress — Yowimo Backend

**Assessed:** 2026-07-13, `dev`@`bd4d056`, by direct code inspection. Analysis only — no code modified.
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

## In progress modules

Real code exists; work remains to finish the module:

| Module | Done | Remaining |
|---|---|---|
| **Party (create/discover)** | Create, discover, show, room codes. | Membership/lifecycle (join/leave/start/end). |
| **User Profile** | View/edit own profile. | Public profile view, avatar upload, account deletion. |
| **Token Bundles** | Catalog list + purchase (top-up). | `show` endpoint; a real payment provider (currently manual/test only). |
| **Horizon / Queue** | Installed, configured. | Gate needs to extend past `local`; no job has ever been dispatched yet. |
| **Sponsorship** | `is_sponsored`/`sponsor_name` columns exist on `parties`. | Everything else — no sponsor entity or flow. |

## Blocked modules

Zero code, and an upstream dependency must land first:

| Module | Blocked on |
|---|---|
| **Game Engine (rounds/turns/timers)** | Party Membership/Lifecycle + Domain Events backbone |
| **Realtime (Reverb)** | Domain Events backbone + Game Engine |
| **Notifications** | Domain Events backbone + active queue |
| **AI Host** | Domain Events backbone + Realtime |
| **Analytics/Observability** | Domain Events backbone |

## Not started modules

Zero code, nothing blocking — ready to schedule:

- CI/CD Pipeline
- Domain Events & Listeners backbone
- Party Membership/Lifecycle
- Friends / Social Graph
- Admin Panel
- Marketplace pack purchase/inventory (Sprint 3) — Wallet API + token bundle purchase (Sprint 2) both now satisfied

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
| `IMPLEMENTATION_ORDER.md` 14-sprint plan | **0 of 14 sprints complete (0%)** — Sprint 1 not yet started |
| Full documented platform vision (`docs/architecture/`) | **~15%** — 3 modules complete, 1 built-unexposed, 6 in progress, the rest (16) not started/blocked/deferred |

`docs/architecture/60_PLATFORM_ROADMAP.md` claims Phase 1 is fully complete including Friends, Marketplace, Notifications, Realtime, and Voice — the code does not support that claim (see `docs/audit/ARCHITECTURE_GAP_ANALYSIS.md`). The figures above are the code-verified numbers.
