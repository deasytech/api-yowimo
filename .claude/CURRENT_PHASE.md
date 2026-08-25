# Current Phase — Yowimo Backend

**Assessed:** 2026-08-23, against `dev` after Sprint 9 landed, by direct code inspection.
**Basis:** `docs/audit/*`, `docs/implementation/IMPLEMENTATION_ORDER.md`, `.claude/PROJECT_CONTEXT.md`.

---

## Current Sprint

**Sprint 9 — Notifications v0** (`docs/implementation/IMPLEMENTATION_ORDER.md`), **done, with scope decided with the user up front.** Nothing blocks starting Sprint 10.

- ✅ `push_tokens` table (`user_id` unique, `token`, `platform`) + `PushToken` model, `HasOne` from `User`. One token per user — registering a new token replaces the previous one (confirmed with the user over a multi-device-per-user model).
- ✅ `App\Services\Notifications\PushTokenService` + `PushTokenController` (`POST /push-tokens`, `DELETE /push-tokens`), inside the existing `auth:clerk` + `throttle:api` group.
- ✅ `kreait/laravel-firebase` installed for FCM (confirmed with the user over a hand-rolled HTTP v1 client) — no config publish needed, package merges its own `config/firebase.php`; project/credentials are read from `FIREBASE_CREDENTIALS`/`GOOGLE_APPLICATION_CREDENTIALS` (documented in `.env.example`, unset by default — push sends are inert until an environment sets real Firebase credentials).
- ✅ `App\Notifications\Channels\FcmChannel` — resolves `Kreait\Firebase\Contract\Messaging` **lazily**, only after confirming the notifiable has a push token. This is a deliberate robustness fix, not incidental: the container's `Messaging` singleton throws immediately if Firebase credentials aren't configured, and constructor-injecting it would crash *every* notification attempt (including for the near-100% of users with no token yet) whenever Firebase isn't set up in an environment. Discovered because it broke unrelated Sprint 6/7 tests (`GameSessionServiceTest`/`GameSessionControllerTest`) that exercise `RoundCompleted` under the `sync` queue driver.
- ✅ Three notifications, each `ShouldQueue`, wired via new listeners (not by touching the events or the services that dispatch them): `PartyMemberJoinedNotification`, `RoundCompletedNotification`, `WalletCreditedNotification`.
- ⚠️ **Scope call made without asking, worth a second look:** `IMPLEMENTATION_ORDER.md`'s Sprint 9 entry names three trigger events but not their recipients. Chosen recipient logic: `PartyMemberJoined` notifies the **party host** only (not the joiner — a self-notification for one's own action isn't useful, and the joiner already knows); `RoundCompleted` notifies **every current party member** (resolved via `PartyMember` for the session's party, not a `turns`/`players` roster specific to the game); `WalletCredited` notifies the credited user directly (unambiguous from the event payload). None of this is stated in the plan — flag if the product intent differs.
- ✅ Push-only this sprint (confirmed with the user over also adding in-app/Reverb-broadcast delivery for the same three events) — `routes/channels.php` and the Sprint 8 broadcast channels are untouched.
- ✅ Only the three named events trigger notifications this sprint (confirmed with the user over covering all nine fired events) — `PartyCreated`, `PartyStarted`, `TurnStarted`, `GameCompleted`, `WalletDebited`, `PurchaseCompleted` do not notify yet.
- ✅ Tests: token register/replace/unregister feature tests; `Notification::fake()` coverage for all three notifications (including the "host, not joiner" and "all party members" recipient rules); one real-queue integration test (`WalletCredited`) that mocks `Kreait\Firebase\Contract\Messaging` and drains the queue with `queue:work --stop-when-empty`, plus a no-token no-op regression test.

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

Chat/Messaging, Friends/social graph, AI Host, Voice/Video (LiveKit), Admin Panel, Moderation/Trust & Safety, Analytics/Observability infrastructure, Creator Economy, Corporate/Multi-Tenant/Enterprise, Internationalization, CI/CD pipeline.

(Marketplace purchase flow/inventory/ownership moved to Partially Complete above — token bundle and pack purchase both now exist; only a real payment gateway is missing. Notifications moved to Partially Complete above.)

---

## Current Priority

Start **Sprint 10 — Friends / social graph** (`docs/implementation/IMPLEMENTATION_ORDER.md`):

1. `friends`/`friend_requests` tables, request/accept/reject endpoints.
2. Independent of the game loop and of Sprint 9 — no shared infrastructure required.
3. **Risk:** low — new, isolated domain with no dependency on money or game state.

Outstanding, unscheduled (needs a design decision before it can be assigned to a sprint):
- Reward granting on round/game completion (amount, trigger, recipients) — explicitly out of Sprint 7 per the user; no sprint in the current 14-sprint plan owns it.
- Broadcasting Wallet/Purchase events and `PartyMemberLeft` — surfaced by Sprint 8; would need a per-user private channel and (for leave) a new domain event that doesn't exist yet.
- Notifications beyond v0: the remaining 6 fired events, in-app (Reverb) delivery, and configuring a real Firebase project per environment — none scheduled.

Lower-priority, not blocking, carried over from Sprint 1:
- Schedule `clerk:sync-users` as an hourly self-heal job.
- Add a GitHub Actions workflow running Pint + Pest on every PR.

---

## Next Recommended Sprint

**Sprint 11 — Admin panel v0**, once Sprint 10 lands: install Filament; resources for Users, Parties, Wallet transactions (read-only/audit), GameTypes/Packs/PackCards/TokenBundles. Risk: low — additive tooling over existing tables.

---

## Overall Completion Percentage

A single number is misleading given the scope gap between the documented vision and the current build target — three reference points instead:

| Reference frame | Completion | Basis |
|---|---|---|
| **Pre-roadmap foundation** (Clerk auth, catalog, party create/like, wallet engine) | **~100%** of its own scope | This slice is finished, tested, and stable — no further work planned against it except the Sprint 1 exposure fix. |
| **`docs/implementation/IMPLEMENTATION_ORDER.md`** (14-sprint actionable plan to a complete, playable core product) | **9 of 14 sprints executed (~64%)** | Sprints 1–9 done (Sprint 7 minus reward-granting, descoped per the user); Sprint 10 (Friends) is next. |
| **Full documented platform vision** (`docs/architecture/`, ~26 modules incl. Marketplace, Realtime, AI, Admin, Enterprise, Creator Economy) | **~27%** | 7 of ~26 modules fully built+exposed (Auth, Game Catalog, Party Likes, Wallet, Marketplace-purchase, Domain Events, Push token registration), 6 partial (incl. Game Engine, Realtime, and the new Notifications), ~13 with zero code. Weighted toward "exists and works," not toward doc page count. |

For context: `docs/architecture/60_PLATFORM_ROADMAP.md` claims "Phase 1: Foundation" is `Status: Completed` including Friends, Marketplace, Notifications, Realtime, and Voice — that claim does not hold against the code (see `docs/audit/ARCHITECTURE_GAP_ANALYSIS.md`). The figures above are the code-verified numbers; treat any completion claim inside `docs/architecture/` as aspirational framing, not status.
