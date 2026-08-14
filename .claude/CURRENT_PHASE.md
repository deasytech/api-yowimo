# Current Phase — Yowimo Backend

**Assessed:** 2026-08-14, against `dev`@`1f81022`, by direct code inspection (no code changed to produce this file).
**Basis:** `docs/audit/*`, `docs/implementation/IMPLEMENTATION_ORDER.md`, `.claude/PROJECT_CONTEXT.md`.

---

## Current Sprint

**Sprint 4 — Party Membership & Lifecycle** (`docs/implementation/IMPLEMENTATION_ORDER.md`), **done.** Two housekeeping items from Sprint 1 remain open (see below) but nothing blocks starting Sprint 5.

- ✅ `party_members` table (`party_id`, `user_id`, `joined_at`, unique per party/user) + `PartyMember` model.
- ✅ `POST /parties/{id}/join` — idempotent (re-joining is a no-op), rejects a full party (409) or a party not in `Scheduled`/`Live` status (422) cleanly.
- ✅ `DELETE /parties/{id}/leave` — idempotent (leaving without membership is a no-op); the host is blocked from leaving their own party (409) and must call `/end` instead.
- ✅ Host-only `POST /parties/{id}/start` (`Draft`/`Scheduled` → `Live`) and `POST /parties/{id}/end` (`Live` → `Ended`), invalid transitions rejected (422); host-only check enforced via `PartyPolicy`, not an inline controller check.
- ✅ `parties.players_count` wired to real membership counts (increment/decrement on join/leave, floor-guarded at zero) — closes `TECHNICAL_DEBT.md` #3.
- ✅ Host is auto-enrolled as a `party_members` row at party creation time (`PartyService::create`), consistent with `players_count` defaulting to 1.
- ✅ `PartyResource` exposes `joined_by_me`, mirroring the existing `liked_by_me` field.

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
| **Party membership & lifecycle** | `party_members` table + `PartyMembershipService`; join/leave/start/end all live, `players_count` wired to real membership counts, host-only start/end enforced via `PartyPolicy`. |

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

---

## Missing Modules

No migration, model, route, or config exists for any of these:

Game Engine (rounds/turns/timers/scoring), Domain events & listeners, Realtime (Reverb), Notifications, Chat/Messaging, Friends/social graph, AI Host, Voice/Video (LiveKit), Admin Panel, Moderation/Trust & Safety, Analytics/Observability infrastructure, Creator Economy, Corporate/Multi-Tenant/Enterprise, Internationalization, CI/CD pipeline.

(Marketplace purchase flow/inventory/ownership moved to Partially Complete above — token bundle and pack purchase both now exist; only a real payment gateway is missing.)

---

## Current Priority

Start **Sprint 5 — Domain events & listeners backbone** (`docs/implementation/IMPLEMENTATION_ORDER.md`):

1. Introduce `app/Events` and `app/Listeners`. Fire events for what already exists: `PartyCreated`, `PartyMemberJoined`, `PartyStarted`, `WalletCredited`, `WalletDebited`, `PurchaseCompleted` — refactoring existing services to dispatch, not changing their outward behavior.
2. Put the first real job on the Horizon queue (e.g., an analytics-event-recording listener) to prove the queue path actually works end-to-end, not just in config.

This touches multiple existing, tested services (Wallet, Purchase, Party, PartyMembership) to add `event()` calls — confirm the event list and dispatch points with the user before starting, per `.claude/ARCHITECTURE_RULES.md`'s precedence note, since it's the first change since Phase 1 that cuts across every core service at once.

Lower-priority, not blocking, carried over from Sprint 1:
- Schedule `clerk:sync-users` as an hourly self-heal job.
- Add a GitHub Actions workflow running Pint + Pest on every PR.

---

## Next Recommended Sprint

**Sprint 6 — Game Engine: rounds & turns (data + state machine)**, once Sprint 5 lands: `game_sessions`, `rounds`, `turns` tables + `GameSessionService`; host-only `POST /parties/{id}/game/start` (requires `Live` party status from Sprint 4) deals cards from the party's `Pack` and advances turns via explicit `POST /game/{id}/next-turn` (poll-driven, no timers yet). Scope to `PackCardKind` (Truth/Dare) only.

---

## Overall Completion Percentage

A single number is misleading given the scope gap between the documented vision and the current build target — three reference points instead:

| Reference frame | Completion | Basis |
|---|---|---|
| **Pre-roadmap foundation** (Clerk auth, catalog, party create/like, wallet engine) | **~100%** of its own scope | This slice is finished, tested, and stable — no further work planned against it except the Sprint 1 exposure fix. |
| **`docs/implementation/IMPLEMENTATION_ORDER.md`** (14-sprint actionable plan to a complete, playable core product) | **0 of 14 sprints executed (0%)** | Sprint 1 has not started; see above. |
| **Full documented platform vision** (`docs/architecture/`, ~26 modules incl. Marketplace, Realtime, AI, Admin, Enterprise, Creator Economy) | **~19%** | 5 of ~26 modules fully built+exposed (Auth, Game Catalog, Party Likes, Wallet, Marketplace-purchase), 4 partial, ~17 with zero code. Weighted toward "exists and works," not toward doc page count. |

For context: `docs/architecture/60_PLATFORM_ROADMAP.md` claims "Phase 1: Foundation" is `Status: Completed` including Friends, Marketplace, Notifications, Realtime, and Voice — that claim does not hold against the code (see `docs/audit/ARCHITECTURE_GAP_ANALYSIS.md`). The 15% figure above is the code-verified number; treat any completion claim inside `docs/architecture/` as aspirational framing, not status.
