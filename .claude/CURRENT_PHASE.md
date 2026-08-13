# Current Phase — Yowimo Backend

**Assessed:** 2026-07-13, against `dev`@`bd4d056`, by direct code inspection (no code changed to produce this file).
**Basis:** `docs/audit/*`, `docs/implementation/IMPLEMENTATION_ORDER.md`, `.claude/PROJECT_CONTEXT.md`.

---

## Current Sprint

**Sprint 3 — Pack Purchase & Inventory** (`docs/implementation/IMPLEMENTATION_ORDER.md`), **done.** Two housekeeping items from Sprint 1 remain open (see below) but nothing blocks starting Sprint 4.

- ✅ `pack_purchases` table (`pack_id`, `user_id`, `wallet_transaction_id`, unique per pack/user) + `PackPurchaseService`, debiting via `WalletService::debit()` unmodified.
- ✅ `POST /packs/{id}/purchase` — validates the pack is active (404 otherwise), requires a server-enforced `Idempotency-Key` header, rejects a repeat purchase of an already-owned pack (409) without a second debit.
- ✅ Race-guarded: a wallet-row lock in `PackPurchaseService` serializes concurrent purchase attempts for the same user so they can't double-charge.
- ✅ `InsufficientWalletBalanceException` path tested end-to-end from the real purchase flow (clean 422, no ownership record created).
- ✅ Full (non-preview) `PackCard` content gated behind ownership — `PackService::find()` loads the full set only for an owner; `PackResource` exposes `owned_by_me`.

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
| **Party Likes** | Like/unlike with idempotent, floor-guarded counters. |
| **Wallet (read API)** | `GET /wallet`, `GET /wallet/transactions` (cursor-paginated) over the existing `WalletService` ledger, `WalletPolicy`-guarded. |
| **Token Bundle purchase (top-up)** | `POST /token-bundles/{id}/purchase` — `PurchaseService` + manual/test `PaymentProvider` driver, credits via `WalletService::credit()`, `Idempotency-Key` enforced. No real payment gateway yet. |
| **Pack purchase & inventory** | `POST /packs/{id}/purchase` — `PackPurchaseService`, debits via `WalletService::debit()`, race-guarded, `Idempotency-Key` enforced, gates full `PackCard` content behind ownership. |

---

## Partially Complete Modules

Real code exists but the module is narrower than its documented scope, or is unreachable:

| Module | What's done | What's missing |
|---|---|---|
| **Wallet** | `WalletService` (unmodified) + a read-only `GET /wallet` / `GET /wallet/transactions` API, `WalletPolicy`, `UserResource` now reports real balance/currency. | No direct wallet write endpoint (top-up happens only via token bundle purchase below). |
| **Party (create/discover)** | Create, list/discover, show, room-code generation, visibility rules. | No join/leave, no start/end, no membership table — a party can never actually be played. |
| **User Profile** | View/edit own profile. | No public profile view, no avatar upload, no account deletion. |
| **Token Bundles** | List (catalog) + `POST /token-bundles/{id}/purchase` (credits wallet, idempotent). | No `show` endpoint. Purchase uses a manual/test `PaymentProvider`, not a real payment gateway. |
| **Packs (catalog + purchase)** | List/discover/show, `POST /packs/{id}/purchase` (debits wallet, grants ownership, full content unlocked). | Nothing planned — scope complete for the current roadmap. |
| **Sponsorship** | `parties.is_sponsored` / `sponsor_name` columns exist. | No sponsor entity, no sponsor-facing flow of any kind — schema hint only. |

---

## Missing Modules

No migration, model, route, or config exists for any of these:

Game Engine (rounds/turns/timers/scoring), Party lifecycle/membership, Domain events & listeners, Realtime (Reverb), Notifications, Chat/Messaging, Friends/social graph, AI Host, Voice/Video (LiveKit), Admin Panel, Moderation/Trust & Safety, Analytics/Observability infrastructure, Creator Economy, Corporate/Multi-Tenant/Enterprise, Internationalization, CI/CD pipeline.

(Marketplace purchase flow/inventory/ownership moved to Partially Complete above — token bundle and pack purchase both now exist; only a real payment gateway is missing.)

---

## Current Priority

Start **Sprint 4 — Party membership & lifecycle** (`docs/implementation/IMPLEMENTATION_ORDER.md`):

1. `party_members` table + model; `POST /parties/{id}/join`, `DELETE /parties/{id}/leave`, host-only `POST /parties/{id}/start` and `POST /parties/{id}/end`.
2. Wire `parties.players_count` to real membership counts (closes the dangling-column debt item in `docs/audit/TECHNICAL_DEBT.md` #3).

This is another new-migration task (`party_members`) — confirm the table design with the user before writing it, same as Sprint 3's `pack_purchases` table, per `.claude/ARCHITECTURE_RULES.md`'s precedence note on introducing new schema.

Lower-priority, not blocking, carried over from Sprint 1:
- Schedule `clerk:sync-users` as an hourly self-heal job.
- Add a GitHub Actions workflow running Pint + Pest on every PR.

---

## Next Recommended Sprint

**Sprint 5 — Domain events & listeners backbone**, once Sprint 4 lands: introduce `app/Events` and `app/Listeners`, retrofitting existing services (Wallet, Party, Purchases) to dispatch events after their transactions commit. This is the highest-leverage infrastructure investment per `docs/implementation/IMPLEMENTATION_ORDER.md`, since Realtime, Notifications, Analytics, and the Game Engine's reward payouts all depend on it.

---

## Overall Completion Percentage

A single number is misleading given the scope gap between the documented vision and the current build target — three reference points instead:

| Reference frame | Completion | Basis |
|---|---|---|
| **Pre-roadmap foundation** (Clerk auth, catalog, party create/like, wallet engine) | **~100%** of its own scope | This slice is finished, tested, and stable — no further work planned against it except the Sprint 1 exposure fix. |
| **`docs/implementation/IMPLEMENTATION_ORDER.md`** (14-sprint actionable plan to a complete, playable core product) | **0 of 14 sprints executed (0%)** | Sprint 1 has not started; see above. |
| **Full documented platform vision** (`docs/architecture/`, ~26 modules incl. Marketplace, Realtime, AI, Admin, Enterprise, Creator Economy) | **~19%** | 5 of ~26 modules fully built+exposed (Auth, Game Catalog, Party Likes, Wallet, Marketplace-purchase), 4 partial, ~17 with zero code. Weighted toward "exists and works," not toward doc page count. |

For context: `docs/architecture/60_PLATFORM_ROADMAP.md` claims "Phase 1: Foundation" is `Status: Completed` including Friends, Marketplace, Notifications, Realtime, and Voice — that claim does not hold against the code (see `docs/audit/ARCHITECTURE_GAP_ANALYSIS.md`). The 15% figure above is the code-verified number; treat any completion claim inside `docs/architecture/` as aspirational framing, not status.
