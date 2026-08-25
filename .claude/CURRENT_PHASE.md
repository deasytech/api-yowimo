# Current Phase — Yowimo Backend

**Assessed:** 2026-08-26, against `dev` after Sprint 10 landed, by direct code inspection.
**Basis:** `docs/audit/*`, `docs/implementation/IMPLEMENTATION_ORDER.md`, `.claude/PROJECT_CONTEXT.md`.

---

## Current Sprint

**Sprint 10 — Friends / social graph** (`docs/implementation/IMPLEMENTATION_ORDER.md`), **done, with scope decided with the user up front.** Nothing blocks starting Sprint 11.

- ✅ `friendships` table (`sender_id`/`receiver_id`/`status`/`accepted_at`, per `docs/architecture/38_DATABASE_SCHEMA_REFERENCE.md`) + `Friendship` model, `sentFriendRequests()`/`receivedFriendRequests()` `HasMany` from `User`. `blocked` stays out of scope for v0 (unchanged from the plan).
- ✅ `App\Services\Friends\FriendshipService` + `FriendshipController` — send (`POST /friend-requests`), accept/reject (`POST /friend-requests/{id}/accept,reject`), cancel a pending outgoing request (`DELETE /friend-requests/{id}`), unfriend (`DELETE /friends/{id}`), list friends (`GET /friends`) and pending requests, both directions (`GET /friend-requests`) — all inside the existing `auth:clerk` + `throttle:api` group, following the Form Request + Resource + Policy pattern (`FriendshipPolicy` gates *who*: sender-only cancel, receiver-only accept/reject, either side to unfriend; the service gates *state*, throwing `InvalidFriendshipTransitionException` on a bad transition).
- ⚠️ **Scope calls made with the user up front, confirmed before coding (not guessed):** (1) cancelling a pending outgoing request and rejecting an incoming one are **distinct operations** (`cancel` vs `reject`, sender vs receiver only); (2) unfriending is a **soft status change** (`removed`), not a hard delete — added `cancelled`/`removed` status values beyond the doc's four (`pending`/`accepted`/`blocked`/`rejected`) to support this; (3) this sprint **does dispatch domain events** — `FriendRequestSent`/`FriendRequestAccepted` (`ShouldDispatchAfterCommit`, unbroadcast — no per-user channel exists yet, same reason Wallet/Purchase/`PartyCreated` aren't broadcast) — for future Notifications/Realtime consumers, but no listener/notification consumes them yet (that remains unscheduled, as the plan intended).
- ✅ Guards duplicate/overlapping requests (a pending request in either direction, or already-accepted) and self-requests (`Rule::notIn` in `StoreFriendshipRequest`), with a `lockForUpdate` transaction in `FriendshipService::send()` to close the race.
- ✅ Tests: full transition coverage (send/accept/reject/cancel/unfriend + every guard/authorization path) in `FriendshipControllerTest`, plus `FriendRequestSent`/`FriendRequestAccepted` dispatch assertions added to the existing `EventDispatchTest`.

Sprint 9 (done previously): `push_tokens` table + `PushTokenService`; `kreait/laravel-firebase` FCM channel (lazily resolved, to avoid crashing every notification attempt when Firebase isn't configured); `PartyMemberJoinedNotification`/`RoundCompletedNotification`/`WalletCreditedNotification` off new listeners (host-only, all-party-members, and credited-user recipient rules respectively); push-only, 3 of 9 fired events wired, confirmed with the user; no real Firebase project configured in any environment yet.

Sprint 8 (done previously): `laravel/reverb`; `party.{id}` presence channel + `game-session.{id}` private channel, both membership-gated; `PartyMemberJoined`/`PartyStarted`/`TurnStarted`/`RoundCompleted`/`GameCompleted` broadcast. Wallet/Purchase/`PartyCreated` events and `PartyMembershipService::leave()` still aren't broadcast (unscheduled, per Sprint 8's notes).

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
| **Push token registration** | `POST`/`DELETE /push-tokens` over `PushTokenService`; one token per user, replace-on-register. |
| **Friends / social graph** | `friendships` table + `FriendshipService`; send/accept/reject/cancel/unfriend + list friends/pending requests, `FriendshipPolicy`-guarded. `FriendRequestSent`/`FriendRequestAccepted` domain events dispatch, unconsumed for now. |

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
| **Realtime (Reverb)** | `laravel/reverb` installed; `party.{id}` presence channel + `game-session.{id}` private channel, both membership-gated; `PartyMemberJoined`/`PartyStarted`/`TurnStarted`/`RoundCompleted`/`GameCompleted` broadcast. | Wallet/Purchase/`PartyCreated` events aren't broadcast (no per-user channel exists yet — deliberately out of Sprint 8's two-channel scope); `PartyMembershipService::leave()` doesn't dispatch any event to broadcast (pre-existing Sprint 5 gap); no live client (React Native) has verified the integration end-to-end. |
| **Notifications** | `push_tokens` table/API; FCM channel; `PartyMemberJoinedNotification`/`RoundCompletedNotification`/`WalletCreditedNotification`, each queued off a new listener. | No real Firebase project/credentials configured in any environment yet (push is wired but inert until `FIREBASE_CREDENTIALS` is set); only 3 of 9 fired events notify; no in-app delivery; no client (React Native) has verified receiving a real push. |

---

## Missing Modules

No migration, model, route, or config exists for any of these:

Chat/Messaging, AI Host, Voice/Video (LiveKit), Admin Panel, Moderation/Trust & Safety, Analytics/Observability infrastructure, Creator Economy, Corporate/Multi-Tenant/Enterprise, Internationalization, CI/CD pipeline.

(Marketplace purchase flow/inventory/ownership moved to Partially Complete above — token bundle and pack purchase both now exist; only a real payment gateway is missing. Notifications and Friends/social graph moved to Completed above.)

---

## Current Priority

Start **Sprint 11 — Admin panel v0** (`docs/implementation/IMPLEMENTATION_ORDER.md`):

1. Install Filament; resources for Users, Parties, Wallet transactions (read-only/audit), GameTypes/Packs/PackCards/TokenBundles.
2. Extend the `viewHorizon` gate to admin roles now that an admin concept exists.
3. **Risk:** low — additive tooling over existing tables; the main risk is scope creep (docs specify a huge admin surface — build only Users/Parties/Content/Wallet-audit now, defer the rest).

Outstanding, unscheduled (needs a design decision before it can be assigned to a sprint):
- Reward granting on round/game completion (amount, trigger, recipients) — explicitly out of Sprint 7 per the user; no sprint in the current 14-sprint plan owns it.
- Broadcasting Wallet/Purchase events and `PartyMemberLeft` — surfaced by Sprint 8; would need a per-user private channel and (for leave) a new domain event that doesn't exist yet.
- Notifications beyond v0: the remaining 6 fired events, in-app (Reverb) delivery, and configuring a real Firebase project per environment — none scheduled.
- Consuming `FriendRequestSent`/`FriendRequestAccepted` for Notifications/Realtime — the events exist (Sprint 10) but nothing listens yet.

Lower-priority, not blocking, carried over from Sprint 1:
- Schedule `clerk:sync-users` as an hourly self-heal job.
- Add a GitHub Actions workflow running Pint + Pest on every PR.

---

## Next Recommended Sprint

**Sprint 12 — Analytics & observability baseline**, once Sprint 11 lands: persist an `analytics_events` feed off the Sprint 5 event backbone; add `/health` checks for DB/Redis/Queue/Broadcast; wire error tracking. Risk: low — read-side/observational work, no changes to write paths.

---

## Overall Completion Percentage

A single number is misleading given the scope gap between the documented vision and the current build target — three reference points instead:

| Reference frame | Completion | Basis |
|---|---|---|
| **Pre-roadmap foundation** (Clerk auth, catalog, party create/like, wallet engine) | **~100%** of its own scope | This slice is finished, tested, and stable — no further work planned against it except the Sprint 1 exposure fix. |
| **`docs/implementation/IMPLEMENTATION_ORDER.md`** (14-sprint actionable plan to a complete, playable core product) | **10 of 14 sprints executed (~71%)** | Sprints 1–10 done (Sprint 7 minus reward-granting, descoped per the user); Sprint 11 (Admin panel) is next. |
| **Full documented platform vision** (`docs/architecture/`, ~26 modules incl. Marketplace, Realtime, AI, Admin, Enterprise, Creator Economy) | **~31%** | 8 of ~26 modules fully built+exposed (Auth, Game Catalog, Party Likes, Wallet, Marketplace-purchase, Domain Events, Push token registration, Friends/social graph), 6 partial (incl. Game Engine, Realtime, and Notifications), ~12 with zero code. Weighted toward "exists and works," not toward doc page count. |

For context: `docs/architecture/60_PLATFORM_ROADMAP.md` claims "Phase 1: Foundation" is `Status: Completed` including Friends, Marketplace, Notifications, Realtime, and Voice — that claim does not hold against the code (see `docs/audit/ARCHITECTURE_GAP_ANALYSIS.md`). The figures above are the code-verified numbers; treat any completion claim inside `docs/architecture/` as aspirational framing, not status.
