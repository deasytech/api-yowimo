# Current Phase — Yowimo Backend

**Assessed:** 2026-08-27, against `dev` after Sprint 12 landed, by direct code inspection.
**Basis:** `docs/audit/*`, `docs/implementation/IMPLEMENTATION_ORDER.md`, `.claude/PROJECT_CONTEXT.md`.

---

## Current Sprint

**Sprint 12 — Analytics & observability baseline** (`docs/implementation/IMPLEMENTATION_ORDER.md`), **done, with four scope calls confirmed with the user up front.** Nothing blocks starting Sprint 13.

- ✅ **Scope calls made with the user up front, confirmed before coding (not guessed):** (1) `RecordAnalyticsEvent` was extended to all six Sprint 5 backbone events (`PartyCreated`, `PartyMemberJoined`, `PartyStarted`, `WalletCredited`, `WalletDebited`, `PurchaseCompleted`), not just `PartyCreated`; (2) it now persists a row and no longer also logs via `Log::info()`; (3) the documented `tenant_id` column was omitted from `analytics_events` — no multi-tenant concept exists anywhere in this codebase; (4) Sentry was the confirmed error-tracking provider and `/health` is public/unauthenticated.
- ✅ `analytics_events` table (`id`, `user_id` nullable, `event`, `payload` json, `ip`/`device`/`country` nullable — present per the doc's column list but never populated this sprint, since no domain event carries request context to plumb through; append-only, no `updated_at`) + `AnalyticsEvent` model.
- ✅ `App\Listeners\RecordAnalyticsEvent::handle()` takes a PHP union type over all six event classes (Laravel's listener auto-discovery registers a union-typed `handle()` for every type in the union — verified via `php artisan event:list`), persists `user_id` + a per-event `payload` shape; `PartyStarted` has no user in its payload so `user_id` is `null` for that event.
- ✅ `GET /api/v1/health` (public, unauthenticated, alongside the existing `/webhooks/clerk` route outside the `auth:clerk` group) — `App\Services\Health\HealthCheckService` checks DB (`DB::connection()->getPdo()`), Redis (`Redis::connection()->ping()`), Queue (`Queue::connection()->size()`), and Broadcast (a raw TCP probe of the configured Reverb host:port, only when `broadcasting.default` is `reverb` — reports `skipped` otherwise). Returns the existing `ApiResponse::success`/`error` envelope, 200 when every check is ok/skipped, 503 if any is down. Laravel's built-in `/up` (registered in `bootstrap/app.php`) was left untouched — it's a liveness probe, not a dependency-status endpoint, so this is additive, not a replacement.
- ✅ `sentry/sentry-laravel` (`^4.27`) installed; `config/sentry.php` published (unedited from package defaults — reads `SENTRY_LARAVEL_DSN`); `Sentry\Laravel\Integration::handles($exceptions)` wired into `bootstrap/app.php`'s existing `withExceptions()` closure, after `ApiExceptionRegistrar::register()`. No DSN configured in any environment yet — same "wired but inert" pattern as Sprint 9's Firebase credentials.
- ✅ Tests: `tests/Feature/Listeners/RecordAnalyticsEventTest.php` (rewritten — queue-push assertion kept, the real-queue-worker test now asserts a persisted row instead of a log line, plus one test per newly-wired event type calling the listener directly); `tests/Feature/Health/HealthCheckServiceTest.php` (ok path + simulated-down path per dependency, including a real ephemeral TCP listener for the broadcast-ok case); `tests/Feature/Health/HealthControllerTest.php` (public access, 200 healthy / 503 unhealthy via a mocked `HealthCheckService`).
- ⚠️ Not built (deliberately, out of this sprint's read-side/observational scope): a Filament Analytics resource/dashboard, Prometheus/Grafana, request-level IP/device capture for `analytics_events` (would require threading request context through every service call site — a separate, larger change).

Sprint 11 (done previously): `filament/filament` v5; `is_admin`-gated `/admin` panel with a separate password-based login on the `web` guard (independent of Clerk); `UserResource` (view/edit, no create/delete), `PartyResource`/`WalletTransactionResource` (view/audit only), `GameTypeResource`/`PackResource`/`PackCardResource`/`TokenBundleResource` (full CRUD, the write path for catalog content). `viewHorizon` gate extended to admins. No in-panel admin password-management UI (unscheduled).

Sprint 10 (done previously): `friendships` table (`sender_id`/`receiver_id`/`status`/`accepted_at`) + `Friendship` model; `App\Services\Friends\FriendshipService` + `FriendshipController` — send/accept/reject/cancel/unfriend, list friends and pending requests both directions, `FriendshipPolicy`-guarded; `blocked` stays out of scope for v0; unfriending is a soft `removed` status, not a hard delete; `FriendRequestSent`/`FriendRequestAccepted` domain events dispatch, unconsumed for now.

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
| **Admin Panel v0** | `filament/filament`; `is_admin`-gated `/admin` panel with a separate password-based login; Users (view/edit), Parties (view/audit), Wallet transactions (view/audit, no write actions registered), GameTypes/Packs/PackCards/TokenBundles (full CRUD). `viewHorizon` gate extended to admins. |
| **Analytics & Observability baseline** | `analytics_events` table + `AnalyticsEvent` model; `RecordAnalyticsEvent` persists a row for all six Sprint 5 backbone events; `GET /api/v1/health` (public) checks DB/Redis/Queue/Broadcast(Reverb); `sentry/sentry-laravel` installed and wired, inert until `SENTRY_LARAVEL_DSN` is set. |

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

Chat/Messaging, AI Host, Voice/Video (LiveKit), Moderation/Trust & Safety, Creator Economy, Corporate/Multi-Tenant/Enterprise, Internationalization, CI/CD pipeline.

(Marketplace purchase flow/inventory/ownership moved to Partially Complete above — token bundle and pack purchase both now exist; only a real payment gateway is missing. Notifications, Friends/social graph, Admin Panel v0, and Analytics & Observability baseline moved to Completed above.)

---

## Current Priority

Start **Sprint 13 — AI Host v0 (narrow scope)** (`docs/implementation/IMPLEMENTATION_ORDER.md`):

1. A single `AIProvider` interface with one concrete OpenAI implementation (no multi-provider fallback chain — premature for v0).
2. One prompt, triggered by `RoundCompleted`/`GameCompleted` (Sprint 5/7 events), delivered as a message into the game's realtime channel (Sprint 8).
3. **Risk:** low-medium — scoped narrowly on purpose; the doc's full "Yowi" persona (voice, moderation, translation, recommendations) is out of scope.

Outstanding, unscheduled (needs a design decision before it can be assigned to a sprint):
- Reward granting on round/game completion (amount, trigger, recipients) — explicitly out of Sprint 7 per the user; no sprint in the current 14-sprint plan owns it.
- Broadcasting Wallet/Purchase events and `PartyMemberLeft` — surfaced by Sprint 8; would need a per-user private channel and (for leave) a new domain event that doesn't exist yet.
- Notifications beyond v0: the remaining 6 fired events, in-app (Reverb) delivery, and configuring a real Firebase project per environment — none scheduled.
- Consuming `FriendRequestSent`/`FriendRequestAccepted` for Notifications/Realtime — the events exist (Sprint 10) but nothing listens yet.
- In-panel password management for admins (Sprint 11 set an admin's password via `tinker`/seeder only — no self-service UI) — no sprint owns this.
- A Filament Analytics resource/dashboard, and populating `analytics_events`' `ip`/`device`/`country` columns (would need request context threaded through every service call site) — surfaced by Sprint 12, neither scheduled.

Lower-priority, not blocking, carried over from Sprint 1:
- Schedule `clerk:sync-users` as an hourly self-heal job.
- Add a GitHub Actions workflow running Pint + Pest on every PR.

---

## Next Recommended Sprint

**Sprint 14 — Hardening pass**, once Sprint 13 lands: expand test coverage toward the now-larger surface; tune rate limits for the new write-heavy endpoints (purchases, joins); run a first security review against `docs/architecture/06`/`52`; stand up backup/DR basics proportionate to actual current infra. Risk: low — this sprint produces no new user-facing behavior, only confidence in what exists.

---

## Overall Completion Percentage

A single number is misleading given the scope gap between the documented vision and the current build target — three reference points instead:

| Reference frame | Completion | Basis |
|---|---|---|
| **Pre-roadmap foundation** (Clerk auth, catalog, party create/like, wallet engine) | **~100%** of its own scope | This slice is finished, tested, and stable — no further work planned against it except the Sprint 1 exposure fix. |
| **`docs/implementation/IMPLEMENTATION_ORDER.md`** (14-sprint actionable plan to a complete, playable core product) | **12 of 14 sprints executed (~86%)** | Sprints 1–12 done (Sprint 7 minus reward-granting, descoped per the user); Sprint 13 (AI Host v0) is next. |
| **Full documented platform vision** (`docs/architecture/`, ~26 modules incl. Marketplace, Realtime, AI, Admin, Enterprise, Creator Economy) | **~38%** | 10 of ~26 modules fully built+exposed (Auth, Game Catalog, Party Likes, Wallet, Marketplace-purchase, Domain Events, Push token registration, Friends/social graph, Admin Panel v0, Analytics & Observability baseline), 6 partial (incl. Game Engine, Realtime, and Notifications), ~10 with zero code. Weighted toward "exists and works," not toward doc page count. |

For context: `docs/architecture/60_PLATFORM_ROADMAP.md` claims "Phase 1: Foundation" is `Status: Completed` including Friends, Marketplace, Notifications, Realtime, and Voice — that claim does not hold against the code (see `docs/audit/ARCHITECTURE_GAP_ANALYSIS.md`). The figures above are the code-verified numbers; treat any completion claim inside `docs/architecture/` as aspirational framing, not status.
