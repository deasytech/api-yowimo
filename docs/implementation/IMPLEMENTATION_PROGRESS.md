# Implementation Progress — Yowimo Backend

**Assessed:** 2026-08-27, after the "broadcast Wallet/Purchase events and PartyMemberLeft" work landed, by direct code inspection.
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
- **Domain events & listeners backbone** — `app/Events`/`app/Listeners`; `PartyCreated`, `PartyMemberJoined`, `PartyStarted`, `WalletCredited`, `WalletDebited`, `PurchaseCompleted` all dispatch fire-after-commit; `RecordAnalyticsEvent` proven end-to-end via a real queue worker.
- **Game Engine timers & completion events** — 30s server-authoritative turn timer (delayed queue job, `afterCommit()`), AFK-skip tracked per turn, crash-recovery sweep (`game:sweep-expired-turns`, scheduled every minute), `RoundCompleted`/`GameCompleted` events. Reward granting was explicitly descoped from this sprint per the user — see In progress below.
- **Push token registration** — `POST`/`DELETE /push-tokens` over `PushTokenService`; one token per user, replace-on-register.
- **Friends / Social Graph** — `friendships` table + `FriendshipService`; send/accept/reject/cancel a pending request, unfriend (soft `removed` status), list friends/pending requests either direction. `FriendRequestSent`/`FriendRequestAccepted` domain events dispatch for future consumers; nothing listens yet.
- **Admin Panel v0** — `filament/filament` v5 panel at `/admin`, gated on a new `is_admin` boolean on `users`, separate password-based login on the `web` guard. `UserResource` (view/edit, no delete), `PartyResource`/`WalletTransactionResource` (view/audit only), `GameTypeResource`/`PackResource`/`PackCardResource`/`TokenBundleResource` (full CRUD — the real write path for catalog content). `viewHorizon` gate extended to admins.
- **Analytics & Observability baseline** — `analytics_events` table + `AnalyticsEvent` model; `RecordAnalyticsEvent` now persists a row (all six Sprint 5 backbone events) instead of only logging. `GET /api/v1/health` (public) checks DB/Redis/Queue/Broadcast(Reverb). `sentry/sentry-laravel` installed and wired, inert until `SENTRY_LARAVEL_DSN` is set.
- **AI Host v0** — `App\Services\AI\AIProvider`/`OpenAiProvider` (a lean `Http`-facade call to OpenAI, no SDK package); `SendAiHostMessage` listener off `GameCompleted` (queued, auto-discovered) builds a playful-host prompt from the completed session and, on success, broadcasts the new `AiHostMessageSent` event onto the existing `game-session.{id}` private channel. Fails silently and logs a warning if OpenAI errors/times out or no key is configured.
- **Hardening pass (Sprint 14)** — manual security review of auth/authorization code (no findings); four new per-user rate limiters (`purchases`, `friend-requests`, `party-actions`, `push-tokens`) stacked via `throttle:` middleware on the existing global `api` limiter; `docs/implementation/BACKUP_AND_DR_BASICS.md` documenting the backup policy to adopt at first production deploy; new rate-limit regression tests. Route paths, request/response payloads, and business logic are unchanged — the only thing that changed on the affected routes is the added `throttle:` middleware.
- **Consume friend-request events (post-Sprint-14)** — `FriendRequestSent`/`FriendRequestAccepted` now broadcast onto a new-but-previously-unused per-user `App.Models.User.{id}` private channel and trigger a queued push notification (`Send*PushNotification` listener + `FcmChannel` `Notification`, mirroring Sprint 9's exact pattern). Also fixed a real bug in `ChannelAuthorizationTest.php`'s `require_once` (should've been `require`) that was silently making every "channel access allowed" test 403 regardless of the authorization closures' actual logic.
- **Broadcast Wallet/Purchase events and `PartyMemberLeft` (post-Sprint-14)** — `WalletCredited`/`WalletDebited`/`PurchaseCompleted`/`PartyCreated` now also implement `ShouldBroadcast`, each broadcasting onto the relevant user's `App.Models.User.{id}` private channel (`WalletCredited`/`WalletDebited`/`PurchaseCompleted` to the transacting user, `PartyCreated` to the host). New `PartyMemberLeft` event, dispatched from `PartyMembershipService::leave()` when a membership is actually removed, broadcasting onto the existing `party.{id}` presence channel alongside `PartyMemberJoined`. No push notifications, routes, or payloads changed — broadcasting only.

## In progress modules

Real code exists; work remains to finish the module:

| Module | Done | Remaining |
|---|---|---|
| **User Profile** | View/edit own profile. | Public profile view, avatar upload, account deletion. |
| **Token Bundles** | Catalog list + purchase (top-up). | `show` endpoint; a real payment provider (currently manual/test only). |
| **Packs** | Catalog + purchase + ownership-gated full content. | Nothing planned — scope complete for now. |
| **Horizon / Queue** | Queue processing itself is active and proven since Sprint 5 — `RecordAnalyticsEvent`, the Sprint 7 turn-timer job, and the Sprint 9 notification jobs are all confirmed end-to-end via a real queue worker in tests. `laravel/horizon` is installed and configured (`config/horizon.php`, dashboard gate); the gate now also allows `is_admin` users (Sprint 11), in addition to its `local`-only bypass. | No process anywhere in this repo (dev script, deploy config) actually runs `php artisan horizon` — local dev runs a plain `php artisan queue:listen` worker instead, so Horizon itself (supervisors, auto-balancing, dashboard data) is unverified as active, only installed. |
| **Sponsorship** | `is_sponsored`/`sponsor_name` columns exist on `parties`. | Everything else — no sponsor entity or flow. |
| **Game Engine (rounds/turns/timers)** | `game_sessions`/`rounds`/`turns` tables + `GameSessionService`; host-only start/next-turn, randomized turn order, host-configurable rounds, Truth/Dare card dealing, auto-completion, 30s turn timer + AFK handling, `RoundCompleted`/`GameCompleted` events. | Votes, scoring, reward granting — unscheduled; rewards were explicitly dropped from Sprint 7 and have no owning sprint in `IMPLEMENTATION_ORDER.md`. |
| **Realtime (Reverb)** | `laravel/reverb` installed; `party.{id}` presence channel + `game-session.{id}` private channel; a per-user `App.Models.User.{id}` private channel; `PartyMemberJoined`/`PartyMemberLeft`/`PartyStarted`/`TurnStarted`/`RoundCompleted`/`GameCompleted`/`FriendRequestSent`/`FriendRequestAccepted`/`WalletCredited`/`WalletDebited`/`PurchaseCompleted`/`PartyCreated` all broadcast. | No live client (React Native) integration verified. |
| **Notifications** | `push_tokens` table/API; `kreait/laravel-firebase` FCM channel (lazily resolved — safe when no token/no Firebase config); `PartyMemberJoinedNotification`/`RoundCompletedNotification`/`WalletCreditedNotification`, each queued off a new listener. | No real Firebase project/credentials configured in any environment (push is wired but inert until `FIREBASE_CREDENTIALS` is set); only 3 of 9 fired events notify; no in-app delivery; no client (React Native) has verified receiving a real push. |

## Blocked modules

None — every module with zero code either has its dependency in place already or is deferred (see below).

## Not started modules

Zero code, nothing blocking — ready to schedule:

- CI/CD Pipeline

**Deferred** (zero code, intentionally not scheduled pending a business trigger — see `IMPLEMENTATION_ORDER.md` §G): Chat/Messaging, Voice/Video (LiveKit), Moderation/Trust & Safety, Creator Economy, Corporate/Multi-Tenant/Enterprise, Internationalization.

---

## Estimated completion

Illustrative only — assumes ~1 engineer-week per sprint per `IMPLEMENTATION_ORDER.md`, starting now (2026-07-13). Adjust to actual team capacity. **Historical note:** these were the original planning-time projections; actual delivery outpaced them — all 14 sprints were done by this doc's 2026-08-27 assessment date, ahead of the Sprint 8–14 dates below (which projected completion as late as 2026-10-12). Left as-is for the per-sprint effort shape, not as a record of when each sprint actually shipped — see "Current Sprint" in `.claude/CURRENT_PHASE.md` for real dates.

| Sprint | Module(s) | Est. week of |
|---|---|---|
| 1 | Wallet API exposure, CI | 2026-07-13 |
| 2–3 | Token + pack purchase (Marketplace v0) | 2026-07-20 – 2026-07-27 |
| 4 | Party membership/lifecycle | 2026-08-03 |
| 5 | Domain events + queue activation | 2026-08-10 |
| 6–7 | Game Engine (rounds/turns, then timers/scoring) | 2026-08-17 – 2026-08-24 |
| 8 | Realtime (Reverb) | 2026-08-31 |
| 9 | Notifications | 2026-09-07 |
| 10 | Friends | 2026-09-14 |
| 11–12 | Admin panel, Analytics baseline | 2026-09-21 – 2026-09-28 |
| 13 | AI Host v0 | 2026-10-05 |
| 14 | Hardening pass | 2026-10-12 |

**~14 weeks (one engineer-quarter) to a complete, playable, monetizable core product**, before any Tier-4/deferred scope is considered.

---

## Overall progress

| Reference frame | Progress |
|---|---|
| Pre-roadmap foundation (Auth, Catalog, Party create/like, Wallet engine) | **~100%** of its own scope — done |
| `IMPLEMENTATION_ORDER.md` 14-sprint plan | **14 of 14 sprints complete (100%)** — the plan is finished; reward granting from Sprint 7's original scope remains descoped and unscheduled; remaining work is unscheduled candidates (see `.claude/NEXT_TASK.md`) |
| Full documented platform vision (`docs/architecture/`) | **~38%** — 10 modules complete, 6 in progress (incl. Game Engine, Realtime, and Notifications), the rest (~10) not started/deferred |

`docs/architecture/60_PLATFORM_ROADMAP.md` claims Phase 1 is fully complete including Friends, Marketplace, Notifications, Realtime, and Voice — the code does not support that claim (see `docs/audit/ARCHITECTURE_GAP_ANALYSIS.md`). The figures above are the code-verified numbers.
