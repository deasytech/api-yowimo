# Current Phase — Yowimo Backend

**Assessed:** 2026-07-13, against `dev`@`bd4d056`, by direct code inspection (no code changed to produce this file).
**Basis:** `docs/audit/*`, `docs/implementation/IMPLEMENTATION_ORDER.md`, `.claude/PROJECT_CONTEXT.md`.

---

## Current Sprint

**Sprint 1 — Foundation Hardening & Wallet Exposure** (`docs/implementation/IMPLEMENTATION_ORDER.md`), **in progress.**

Wallet exposure is done; two Sprint 1 items remain:
- ✅ `UserResource` now reports real `wallet.balance`/`wallet.currency` (stub removed).
- ✅ `WalletController` / `GET /wallet` / `GET /wallet/transactions` routes ship, guarded by `WalletPolicy`, backed unmodified by `WalletService`.
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
| **Party Likes** | Like/unlike with idempotent, floor-guarded counters. |
| **Wallet (read API)** | `GET /wallet`, `GET /wallet/transactions` (cursor-paginated) over the existing `WalletService` ledger, `WalletPolicy`-guarded. No purchase/top-up (write) endpoint yet. |

---

## Partially Complete Modules

Real code exists but the module is narrower than its documented scope, or is unreachable:

| Module | What's done | What's missing |
|---|---|---|
| **Wallet** | `WalletService` (unmodified) + a read-only `GET /wallet` / `GET /wallet/transactions` API, `WalletPolicy`, `UserResource` now reports real balance/currency. | No purchase/top-up (write) endpoint yet — Sprint 2. |
| **Party (create/discover)** | Create, list/discover, show, room-code generation, visibility rules. | No join/leave, no start/end, no membership table — a party can never actually be played. |
| **User Profile** | View/edit own profile. | No public profile view, no avatar upload, no account deletion. |
| **Token Bundles** | List (catalog) only. | No `show`, no purchase/checkout endpoint, nothing wired to the wallet. |
| **Sponsorship** | `parties.is_sponsored` / `sponsor_name` columns exist. | No sponsor entity, no sponsor-facing flow of any kind — schema hint only. |

---

## Missing Modules

No migration, model, route, or config exists for any of these:

Game Engine (rounds/turns/timers/scoring), Marketplace (purchase flow/inventory/ownership), Party lifecycle/membership, Domain events & listeners, Realtime (Reverb), Notifications, Chat/Messaging, Friends/social graph, AI Host, Voice/Video (LiveKit), Admin Panel, Moderation/Trust & Safety, Analytics/Observability infrastructure, Creator Economy, Corporate/Multi-Tenant/Enterprise, Internationalization, CI/CD pipeline.

---

## Current Priority

Finish out the remainder of **Sprint 1**:

1. ~~Wire real `Wallet`/`WalletService` data into `UserResource`; remove the stale stub.~~ Done.
2. ~~Add `GET /wallet` and `GET /wallet/transactions` (thin controller over the existing, already-correct service) + a `WalletPolicy`.~~ Done.
3. Schedule `clerk:sync-users` as an hourly self-heal job.
4. Add a GitHub Actions workflow running Pint + Pest on every PR.

None of this touches `WalletService` internals or any existing schema — it's pure exposure and process hardening, chosen specifically to unblock the commerce work (Sprints 2–3) without introducing new structural risk.

---

## Next Recommended Sprint

**Sprint 2 — Token Purchase (wallet top-up)**, once Sprint 1 lands: a `PurchaseService` with a pluggable payment-provider interface (manual/test driver for now), `POST /token-bundles/{id}/purchase` calling the existing `WalletService::credit()` idempotently. This is the first module that connects the now-exposed wallet to a real user-facing money-in flow, and it's sequenced immediately after Sprint 1 because it reuses that sprint's exposure work directly.

---

## Overall Completion Percentage

A single number is misleading given the scope gap between the documented vision and the current build target — three reference points instead:

| Reference frame | Completion | Basis |
|---|---|---|
| **Pre-roadmap foundation** (Clerk auth, catalog, party create/like, wallet engine) | **~100%** of its own scope | This slice is finished, tested, and stable — no further work planned against it except the Sprint 1 exposure fix. |
| **`docs/implementation/IMPLEMENTATION_ORDER.md`** (14-sprint actionable plan to a complete, playable core product) | **0 of 14 sprints executed (0%)** | Sprint 1 has not started; see above. |
| **Full documented platform vision** (`docs/architecture/`, ~26 modules incl. Marketplace, Realtime, AI, Admin, Enterprise, Creator Economy) | **~15%** | 3 of ~26 modules fully built+exposed, 1 fully built but unexposed, 4 partial, ~18 with zero code. Weighted toward "exists and works," not toward doc page count. |

For context: `docs/architecture/60_PLATFORM_ROADMAP.md` claims "Phase 1: Foundation" is `Status: Completed` including Friends, Marketplace, Notifications, Realtime, and Voice — that claim does not hold against the code (see `docs/audit/ARCHITECTURE_GAP_ANALYSIS.md`). The 15% figure above is the code-verified number; treat any completion claim inside `docs/architecture/` as aspirational framing, not status.
