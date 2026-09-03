# Current Phase — Yowimo Backend

**Assessed:** 2026-09-03, against `dev` after the "Badges & Achievements" work landed, by direct code inspection.
**Basis:** `docs/audit/*`, `docs/implementation/IMPLEMENTATION_ORDER.md`, `.claude/PROJECT_CONTEXT.md`.

---

## Current Sprint

**Post-Sprint-14 — Badges & Achievements (Reward Engine, Phase 2)** (unscheduled item from `.claude/NEXT_TASK.md`'s "Reward Engine" candidate — scoped down to a confirmed slice, not the full remaining engine; badges-only, with daily streaks/combo multipliers/sponsor rewards/leaderboards explicitly deferred, confirmed with the user up front the same way each prior sprint's scope was confirmed), **done.** Adds two new routes (`GET /badges`, `GET /users/me/badges`); no existing route, request/response shape, or business logic changed.

- ✅ `badges` (catalog) and `user_badges` (append-only earned-badge ledger, `unique(user_id, badge_id)`) tables + `Badge`/`UserBadge` models — mirrors the `xp_transactions` append-only pattern exactly (`booted()` blocks update/delete).
- ✅ `App\Services\Game\BadgeService::award()` — idempotent (duplicate-key catch, same style as `VoteService`), and tolerant of a missing catalog row (returns `null` rather than throwing) so a badge-awarding side effect can never fail the gameplay action it's attached to.
- ✅ Seven badges seeded via `BadgeSeeder`, criteria grounded in existing data since `08_GAME_ENGINE.md`'s Achievement Engine section gives only example names, no thresholds: First Party (1st completed game), 100 Parties (100th), Perfect Game (no AFK-skipped turns in a completed game — merges the doc's separately-named "No Skips"), Party King (first MVP bonus, awarded inline from `GrantMvpBonus`), Truth Master / Dare Devil (25 completed turns of that `PackCard` kind), Social Butterfly (10 accepted friendships).
- ✅ New listeners hook into existing events only — no new domain events needed to trigger evaluation: `EvaluateGameCompletionBadges` (`GameCompleted`), `EvaluateTurnCompletionBadges` (`TurnCompleted`), `EvaluateFriendshipBadges` (`FriendRequestAccepted`). New `BadgeAwarded` event (broadcasts on `App.Models.User.{id}`) + `SendBadgeAwardedNotification` deliver a `BadgeAwardedNotification` over the existing `FcmChannel`/`InAppChannel`, mirroring the `Send*PushNotification` pattern exactly.
- ✅ `GET /badges` (catalog) and `GET /users/me/badges` (earned, `Cache-Control: no-store` since it's personal data — same fix already applied to `NotificationController::index()`), both cursor-paginated, mirroring `GameTypeController`/`WalletController`'s shape.
- ✅ Threshold checks use `>=`/`<` rather than exact equality — these listeners are `ShouldQueue`; a permanently-lost job at the exact threshold count would otherwise mean the badge is never awarded, since a later invocation would see a count that's already skipped past it (found in review, fixed same task).
- Explicitly out of scope: Daily Streak (no reset/timezone rules defined anywhere), Combo Multiplier (the doc marks this "(Future)" itself), Sponsor Rewards (undefined business/payment terms), Leaderboards (not specified beyond being listed as a "Reward Type") — still unscheduled.
- ✅ Tests: `EvaluateGameCompletionBadgesTest`, `EvaluateTurnCompletionBadgesTest`, `EvaluateFriendshipBadgesTest`, a `GrantMvpBonusTest` addition for Party King, `BadgeControllerTest` (catalog list, earned-badges scoping/ordering, no-store header).
- ✅ Full suite (306 tests) and Pint both pass with no regressions.

**Post-Sprint-14 — Voting Engine + XP scoring (Reward Engine, Phase 1)** (unscheduled item from `.claude/NEXT_TASK.md`'s "Reward Engine beyond the flat game-completion grant" candidate — scoped down to a confirmed first slice, not the full engine; voting-after-each-turn confirmed with the user up front, the same way each prior sprint's scope was confirmed), **done.** Adds one new route (`POST /game/{gameSession}/turns/{turn}/vote`); no existing route, request/response shape, or business logic changed except one additive field (`xp`) on `UserResource`.

- ✅ New `App\Events\TurnCompleted` (mirrors `RoundCompleted`'s shape exactly), dispatched from `GameSessionService::nextTurn()`/`skipAfkTurn()` right after a turn's `completed_at` is set — the signal a turn just finished and voting is open, and the trigger for the "Challenge Completed" XP grant.
- ✅ `votes` table + `App\Models\Vote` (append-only, `unique(turn_id, voter_id, category)` at the DB layer) — any party member other than the turn's own player (`App\Policies\TurnPolicy::vote`) can cast one `winner`/`funny`/`creativity` vote per category per completed, non-AFK turn.
- ✅ XP ledger mirroring the `Wallet`/`WalletTransaction`/`WalletService` pattern exactly (minus debit — XP is credit-only, nothing spends it yet): `users.xp` cached column, append-only `xp_transactions` table, `App\Services\Game\XpService::credit()`.
- ✅ Point values taken directly from `docs/architecture/08_GAME_ENGINE.md`'s Scoring Engine example: Winner vote +25, Funny vote +15, Creativity vote +20 (`App\Services\Game\VoteService::cast()`), Challenge Completed +50 (`App\Listeners\GrantChallengeCompletionXp`, off `TurnCompleted`, skipped for AFK-skipped turns), MVP bonus +100 (`App\Listeners\GrantMvpBonus`, off `GameCompleted`, awarded to every player tied for the highest XP earned in that game — an explicit tie-break choice, since Challenge Completed alone makes ties common).
- ✅ `App\Events\VoteCast` broadcasts on the existing `game-session.{id}` channel (already authorized for party members in `routes/channels.php`, no channel-auth changes needed).
- Explicitly out of scope per the user's Phase-1 answer: Badges/Achievements, Daily Streaks, Combo multipliers (the doc marks this "(Future)" itself), sponsor/advertisement rewards, leaderboard endpoints — still unscheduled.
- ✅ Tests: `TurnVoteControllerTest` (auth, self-vote-forbidden, non-member-forbidden, not-yet-completed-422, duplicate-vote-409, invalid-category-422, success + XP assertion), `GrantChallengeCompletionXpTest`, `GrantMvpBonusTest` (queued-assertion + real-run pattern, mirroring `GrantGameCompletionRewardTest`, including a tie case and a votes-differentiate-the-winner case).
- ✅ Full suite (291 tests) and Pint both pass with no regressions.

**Post-Sprint-14 — In-app notifications** (recommendation written up in `.claude/NEXT_TASK.md` after re-running the dependency analysis against `docs/architecture/00`–`60` directly, confirmed by the user, then implemented), **done.** Adds three new routes (`GET /notifications`, `PATCH /notifications/read`, `PATCH /notifications/read-all`); no existing route, request/response shape, or business logic changed — everything else here is new, additive scope only.

- ✅ `notifications` table + `App\Models\Notification` (`id, user_id, title, body, type, read_at, metadata, created_at`), matching the exact schema in `docs/architecture/38_DATABASE_SCHEMA_REFERENCE.md` — a plain `user_id`-keyed table, not Laravel's default polymorphic notifications package table (same "bespoke table over generic package one" pattern as `analytics_events` from Sprint 12).
- ✅ `App\Notifications\Channels\InAppChannel`, a new custom notification channel (alongside the existing `FcmChannel`) that persists a row when a notification implements `toInApp()`. All 10 existing `*Notification` classes (the same ones wired to push across Sprint 9 and the two most recent post-Sprint-14 items) now also implement `toInApp()` and list `InAppChannel::class` in `via()` — same title/body/type content already generated for FCM, so the two channels can't drift on *whether* something fires, only on delivery mechanism. The WalletCredited/WalletDebited double-notification suppression added earlier this session (skip when `reference_type` is set) lives in the listener, before `notify()` is called — so it suppresses both channels together, not just push.
- ✅ `GET /notifications` (cursor-paginated, newest first, scoped to the authenticated user — mirrors `WalletController::transactions`'s shape/pattern exactly), `PATCH /notifications/read` (body: `notification_id`; 404s if it doesn't belong to the caller, scoped-query pattern rather than a separate Policy, mirroring `PushTokenService`), `PATCH /notifications/read-all` — matches the exact endpoints in `docs/architecture/39_REST_API_REFERENCE.md`.
- ✅ `User::notifications()` deliberately overrides the `Notifiable` trait's built-in database-notifications relation (which targets Laravel's default schema) to point at this app's own `Notification` model instead — documented inline since it's a non-obvious shadow, not an accident.
- Live delivery: no new broadcast was added for the notification row itself. The 10 underlying domain events already broadcast on `App.Models.User.{id}`/`party.{id}` (Sprint 8 + the two most recent post-Sprint-14 items), so a connected client already gets a live signal when one of these happens; `GET /notifications` is the persisted, read-stateful, catch-up-when-offline feed on top of that — not a duplicate live channel. `notification_preferences` (named with no column spec in doc 38) and a real Firebase project stay out of scope, as documented in `NEXT_TASK.md` before this was built.
- ✅ Tests: `NotificationFactory`; `tests/Feature/Api/V1/NotificationControllerTest.php` (list/pagination/ownership-scoping, mark-one-read, mark-all-read, 404 on another user's notification, 401 with no token); `tests/Feature/Notifications/InAppChannelTest.php` (persists on `toInApp`, no-ops without it); `tests/Feature/Notifications/InAppNotificationDeliveryTest.php` (three representative events prove the real event → listener → both-channels path, including the wallet/purchase suppression applying to the in-app channel too).
- ✅ Full suite (275 tests) and Pint both pass with no regressions.

**Post-Sprint-14 — Wire remaining events to push** (unscheduled item from `.claude/NEXT_TASK.md`'s "Notifications beyond v0" candidate, picked up by user decision — the specific events and their recipient rules were confirmed with the user up front, the same way Sprint 9's original three were), **done.** No route, request/response shape, or existing business logic changed.

- ✅ Five more of the domain events that already fire now also send a push notification, following Sprint 9's exact `Send*PushNotification` listener → `*Notification` class pattern: `PartyStarted` (all party members except the host who started it), `GameCompleted` (every party member, mirrors `RoundCompleted`), `PartyMemberLeft` (host only, mirrors `PartyMemberJoined`'s host-only rule), `WalletDebited` (the debited user, mirrors `WalletCredited`), `PurchaseCompleted` (the purchasing user, message reused from the wallet transaction's existing `description` field).
- ✅ `PartyCreated` (self-triggered by the host — no one else to notify at creation time) and `TurnStarted` (fires as often as every ~30s per the server-authoritative turn timer) were deliberately left without push notifications — confirmed with the user as out of scope for this task, not an oversight.
- ✅ Tests: five new `tests/Feature/Listeners/Send*PushNotificationTest.php` files (queued-onto-the-queue assertion + correct-recipient(s) assertion each, mirroring the existing `SendPartyMemberJoinedPushNotificationTest`/`SendRoundCompletedPushNotificationTest` two-test shape).
- ✅ Double-notification fix (user-directed follow-up, same task): a pack purchase already dispatched both `WalletDebited` and `PurchaseCompleted`, and a token-bundle purchase already dispatched both `WalletCredited` and `PurchaseCompleted` — wiring both to push meant purchases fired two notifications instead of one. Fixed by having `SendWalletCreditedPushNotification`/`SendWalletDebitedPushNotification` skip sending when the transaction has a `reference_type` set (currently true only for purchase-linked transactions, since `PurchaseService`/`PackPurchaseService` are the only call sites that pass a `reference`) — `PurchaseCompletedNotification` already covers that case with a more specific message. The generic wallet notification still fires for any future non-purchase credit/debit (admin corrections, refunds, rewards) that doesn't pass a reference. Two new regression tests lock this in.
- ✅ Full suite (264 tests) and Pint both pass with no regressions.

**Post-Sprint-14 — Broadcast Wallet/Purchase events and `PartyMemberLeft`** (unscheduled item surfaced by Sprint 8, picked up by user decision from a set of candidates — not a numbered sprint in `IMPLEMENTATION_ORDER.md`, which ends at Sprint 14), **done.** No route, request/response shape, or existing business logic changed.

- ✅ `WalletCredited`, `WalletDebited`, `PurchaseCompleted`, and `PartyCreated` now also implement `ShouldBroadcast`, each broadcasting onto the relevant user's own `App.Models.User.{id}` private channel (the same channel `FriendRequestSent`/`FriendRequestAccepted` started using post-Sprint-14) as `wallet.credited`, `wallet.debited`, `purchase.completed`, and `party.created` respectively. `WalletCredited`/`WalletDebited`/`PurchaseCompleted` broadcast to the transacting user; `PartyCreated` broadcasts to the host (for multi-device sync).
- ✅ New `App\Events\PartyMemberLeft` event, dispatched from `PartyMembershipService::leave()` only when a membership row was actually deleted (mirrors `join()`'s idempotent-dispatch guard), broadcasting onto the existing `party.{id}` presence channel as `party.member.left` — the same channel `PartyMemberJoined` already uses.
- ✅ No new push notifications added — this sprint was scoped to realtime broadcasting only, not the notification system (`RecordAnalyticsEvent`, the FCM notification classes, and rate limiters are all unchanged).
- ✅ Tests: `EventDispatchTest.php` (new `PartyMemberLeft` dispatch/idempotency case, `broadcastOn()` assertions for all five events). No new channel-authorization tests needed — `App.Models.User.{id}` and `party.{id}` access rules were already covered by existing `ChannelAuthorizationTest.php` cases. Updated `RecordAnalyticsEventTest.php`'s real-queue-worker test: `PartyCreated` becoming broadcastable means the framework now queues a second `BroadcastEvent` job alongside `RecordAnalyticsEvent`'s listener job, so the test now expects/drains 2 queued jobs instead of 1 (an intentional behavior change, not a pre-existing bug).

**Post-Sprint-14 — Consume `FriendRequestSent`/`FriendRequestAccepted`** (unscheduled item from Sprint 10, picked up by user decision), done. Push notification + push (delivery channel confirmed with the user as "push + realtime", the latter also confirmed with the user up front). No route, request/response shape, or existing business logic changed.

- ✅ `App\Events\FriendRequestSent`/`FriendRequestAccepted` now also implement `ShouldBroadcast`, each broadcasting onto the *other* party's private notification channel (`App.Models.User.{id}`, already authorized in `routes/channels.php` but unused until now) as `friend.request.sent`/`friend.request.accepted`.
- ✅ Two new queued listeners (`SendFriendRequestSentPushNotification`, `SendFriendRequestAcceptedPushNotification`) + two new `Notification` classes (`FriendRequestSentNotification`, `FriendRequestAcceptedNotification`) over the existing `FcmChannel`, mirroring the exact Sprint 9 pattern (`Send*PushNotification` listener → `*Notification` class).
- ✅ Recipient rule: `FriendRequestSent` notifies the receiver; `FriendRequestAccepted` notifies the original sender (the person whose request got accepted) — mirrors who'd actually want to know.
- ✅ Found and fixed a real bug while adding channel-auth tests for the (previously untested) `App.Models.User.{id}` channel: `tests/Feature/Broadcasting/ChannelAuthorizationTest.php`'s `beforeEach` used `require_once` to re-register `routes/channels.php` against a reconfigured `reverb` broadcaster, but the file had already run once during normal app boot (against the `null` driver from `BROADCAST_CONNECTION` in `phpunit.xml`) — `require_once` is a no-op on an already-included path, so the `reverb` driver instance always had zero channels and every subscription attempt 403'd regardless of the closures' actual logic. Fixed to `require`; this also fixed the two previously-failing tests in that file (party presence channel, game-session private channel) that Sprint 14 had documented as pre-existing/unrelated.
- ✅ Tests: `EventDispatchTest.php` (broadcastOn assertions), two new listener test files (queued + correct recipient/no-op for the other party), two new `ChannelAuthorizationTest.php` cases (own channel allowed, other user's rejected).

Sprint 14 — Hardening pass (done previously): manual security review (no findings); four new per-user rate limiters (`purchases` 10/min, `friend-requests` 20/min, `party-actions` 30/min, `push-tokens` 10/min) stacked on the existing global `api` limiter; `docs/implementation/BACKUP_AND_DR_BASICS.md`; rate-limit regression tests. The last numbered sprint in `IMPLEMENTATION_ORDER.md`.

- ✅ **Security review:** manual audit of Clerk JWT verification, JWKS caching, webhook signature verification (`svix/svix`), authorization policies, mass-assignment, and wallet idempotency scoping against `docs/architecture/06_SECURITY_STANDARDS.md`/`52_SECURITY_PLAYBOOK.md`. No high-confidence vulnerabilities found.
- ✅ **Rate limits:** four new per-user `RateLimiter` definitions in `AppServiceProvider` — `purchases` (10/min), `friend-requests` (20/min), `party-actions` (30/min), `push-tokens` (10/min) — stacked via `throttle:` middleware on the relevant routes in `routes/api.php`, on top of the pre-existing global `api` limiter (60/min, unchanged).
- ✅ **Backup/DR basics:** `docs/implementation/BACKUP_AND_DR_BASICS.md` — since no production infra is provisioned yet (no Docker/IaC/CI, local-only `.env`), it documents the policy to adopt at first deploy (automated daily backups, PITR, 7-day retention, quarterly restore drill) rather than describing infra that doesn't exist.
- ✅ **Test coverage:** `tests/Feature/Api/V1/RateLimitingTest.php` — 4 new tests proving each new limiter throttles at its threshold and is scoped per-user, not global. AI Host and party/game-session modules were checked and already had solid dedicated coverage (including confirming the AFK-skip completion path shares the same `GameCompleted` dispatch as the manual next-turn path), so no additional tests were added there.

Sprint 13 (done previously): `App\Services\AI\AIProvider`/`OpenAiProvider` (lean `Http`-facade call to OpenAI, no SDK package); `App\Listeners\SendAiHostMessage` (`ShouldQueue`, off `GameCompleted` only) builds a prompt from the completed session and broadcasts the new `AiHostMessageSent` event onto `game-session.{id}` (`ai-host.message`); playful in-character tone; skip-silently-and-log on any provider failure; `gpt-4o-mini` via `OPENAI_MODEL`; inert until `OPENAI_API_KEY` is set. Full "Yowi" persona, `RoundCompleted` trigger, and retry/backoff remain unscheduled.

Sprint 12 (done previously): `analytics_events` table + `AnalyticsEvent` model; `RecordAnalyticsEvent` persists a row for all six Sprint 5 backbone events (`PartyCreated`, `PartyMemberJoined`, `PartyStarted`, `WalletCredited`, `WalletDebited`, `PurchaseCompleted`), replacing its prior log-only behavior; `GET /api/v1/health` (public) checks DB/Redis/Queue/Broadcast(Reverb); `sentry/sentry-laravel` installed and wired, inert until `SENTRY_LARAVEL_DSN` is set.

Sprint 11 (done previously): `filament/filament` v5; `is_admin`-gated `/admin` panel with a separate password-based login on the `web` guard (independent of Clerk); `UserResource` (view/edit, no create/delete), `PartyResource`/`WalletTransactionResource` (view/audit only), `GameTypeResource`/`PackResource`/`PackCardResource`/`TokenBundleResource` (full CRUD, the write path for catalog content). `viewHorizon` gate extended to admins. No in-panel admin password-management UI (unscheduled).

Sprint 10 (done previously): `friendships` table (`sender_id`/`receiver_id`/`status`/`accepted_at`) + `Friendship` model; `App\Services\Friends\FriendshipService` + `FriendshipController` — send/accept/reject/cancel/unfriend, list friends and pending requests both directions, `FriendshipPolicy`-guarded; `blocked` stays out of scope for v0; unfriending is a soft `removed` status, not a hard delete; `FriendRequestSent`/`FriendRequestAccepted` domain events dispatch (now consumed — see Current Sprint above).

Sprint 9 (done previously): `push_tokens` table + `PushTokenService`; `kreait/laravel-firebase` FCM channel (lazily resolved, to avoid crashing every notification attempt when Firebase isn't configured); `PartyMemberJoinedNotification`/`RoundCompletedNotification`/`WalletCreditedNotification` off new listeners (host-only, all-party-members, and credited-user recipient rules respectively); push-only, 3 of 9 fired events wired, confirmed with the user; no real Firebase project configured in any environment yet.

Sprint 8 (done previously): `laravel/reverb`; `party.{id}` presence channel + `game-session.{id}` private channel, both membership-gated; `PartyMemberJoined`/`PartyStarted`/`TurnStarted`/`RoundCompleted`/`GameCompleted` broadcast. Wallet/Purchase/`PartyCreated` events and `PartyMembershipService::leave()` still aren't broadcast (unscheduled, per Sprint 8's notes).

Sprint 7 (done previously): server-authoritative 30s turn timer (delayed queue job) with AFK-skip tracked per turn, crash-recovery sweep (`game:sweep-expired-turns`), `RoundCompleted`/`GameCompleted` events. Reward-granting was explicitly descoped from Sprint 7 by the user; picked up later as its own unscheduled post-Sprint-14 item (game-completion tokens, then Voting Engine + XP scoring — see Current Sprint above), not as part of Sprint 7's own scope.

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
| **Friends / social graph** | `friendships` table + `FriendshipService`; send/accept/reject/cancel/unfriend + list friends/pending requests, `FriendshipPolicy`-guarded. `FriendRequestSent`/`FriendRequestAccepted` now broadcast onto the recipient's `App.Models.User.{id}` private channel and trigger a push notification (`Send*PushNotification` listeners + `Fcm`-channel `Notification` classes). |
| **Admin Panel v0** | `filament/filament`; `is_admin`-gated `/admin` panel with a separate password-based login; Users (view/edit), Parties (view/audit), Wallet transactions (view/audit, no write actions registered), GameTypes/Packs/PackCards/TokenBundles (full CRUD). `viewHorizon` gate extended to admins. |
| **Analytics & Observability baseline** | `analytics_events` table + `AnalyticsEvent` model; `RecordAnalyticsEvent` persists a row for all six Sprint 5 backbone events; `GET /api/v1/health` (public) checks DB/Redis/Queue/Broadcast(Reverb); `sentry/sentry-laravel` installed and wired, inert until `SENTRY_LARAVEL_DSN` is set. |
| **AI Host v0** | `App\Services\AI\AIProvider`/`OpenAiProvider`; `SendAiHostMessage` listener off `GameCompleted` broadcasts a playful AI-generated message via the new `AiHostMessageSent` event onto `game-session.{id}`; skip-silently-and-log on OpenAI failure; inert until `OPENAI_API_KEY` is set. |

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
| **Game Engine (rounds/turns/timers)** | `game_sessions`/`rounds`/`turns` tables + `GameSessionService`; host-only start/next-turn, randomized turn order, host-configurable rounds, Truth/Dare card dealing with no-repeat-until-exhausted, auto-completion; 30s server-authoritative turn timer with AFK-skip (tracked per turn), crash-recovery sweep, `RoundCompleted`/`GameCompleted`/`TurnCompleted` events. Voting + XP scoring (Reward Engine Phase 1): `votes` table, Winner/Funny/Creativity vote XP, Challenge Completed XP, MVP bonus. Badges/Achievements (Reward Engine Phase 2, see Current Sprint above): 7 badges, `GET /badges`, `GET /users/me/badges`. | The rest of the documented Reward/Scoring/Achievement Engine — daily streaks, combo multipliers, sponsor/advertisement rewards, leaderboards — none of that was built; still unscheduled (see `.claude/NEXT_TASK.md`). |
| **Realtime (Reverb)** | `laravel/reverb` installed; `party.{id}` presence channel + `game-session.{id}` private channel, both membership-gated; a per-user `App.Models.User.{id}` private channel (used by `FriendRequestSent`/`FriendRequestAccepted`/`WalletCredited`/`WalletDebited`/`PurchaseCompleted`/`PartyCreated`); `PartyMemberJoined`/`PartyMemberLeft`/`PartyStarted`/`TurnStarted`/`RoundCompleted`/`GameCompleted`/`FriendRequestSent`/`FriendRequestAccepted`/`WalletCredited`/`WalletDebited`/`PurchaseCompleted`/`PartyCreated` all broadcast. | No live client (React Native) has verified the integration end-to-end. |
| **Notifications** | `push_tokens` table/API; FCM channel; `PartyMemberJoinedNotification`/`RoundCompletedNotification`/`WalletCreditedNotification`/`FriendRequestSentNotification`/`FriendRequestAcceptedNotification`/`PartyStartedNotification`/`GameCompletedNotification`/`PartyMemberLeftNotification`/`WalletDebitedNotification`/`PurchaseCompletedNotification`, each queued off a new listener, delivered over both `FcmChannel` and the new `InAppChannel`; `notifications` table + `GET /notifications`/`PATCH /notifications/read`/`PATCH /notifications/read-all`. | No real Firebase project/credentials configured in any environment yet (push is wired but inert until `FIREBASE_CREDENTIALS` is set); `PartyCreated` (self-triggered) and `TurnStarted` (fires up to every ~30s) are deliberately not wired to either channel; no `notification_preferences` (per-channel opt-in/opt-out); no client (React Native) has verified receiving a real push. |

---

## Missing Modules

No migration, model, route, or config exists for any of these:

Chat/Messaging, Voice/Video (LiveKit), Moderation/Trust & Safety, Creator Economy, Corporate/Multi-Tenant/Enterprise, Internationalization, CI/CD pipeline.

(Marketplace purchase flow/inventory/ownership and Notifications moved to Partially Complete above — token bundle and pack purchase both now exist, only a real payment gateway is missing; Notifications now covers push and in-app delivery, only a real Firebase project and `notification_preferences` are missing. Friends/social graph, Admin Panel v0, Analytics & Observability baseline, and AI Host v0 moved to Completed above.)

---

## Current Priority

**Nothing is scheduled.** Sprint 14 was explicitly the last sprint in `IMPLEMENTATION_ORDER.md`'s 14-sprint plan, and the doc (`IMPLEMENTATION_ORDER.md:205`) says any further work should be planned fresh with the user, not assumed from this document. The friend-request-events item, the "wire remaining events to push" item, and the "in-app notifications" item below have since been picked up and shipped (see Current Sprint above). See `.claude/NEXT_TASK.md` for the remaining candidates and the "If Ambiguous" guidance — the short version: ask the user which one to build next before writing any code.

Outstanding, unscheduled (needs a design decision before it can be assigned to a sprint):
- Reward granting beyond Phase 2 (daily streaks, combo multipliers, sponsor/advertisement rewards, leaderboards) — explicitly out of Sprint 7 per the user, no sprint in the current 14-sprint plan owns it. (Voting + XP scoring and Badges/Achievements are now done, picked up post-Sprint-14 as their own confirmed slices; see Current Sprint above.)
- Notifications beyond v0, remaining scope: `notification_preferences` (per-channel opt-in/opt-out, named with no column spec in `docs/architecture/38_DATABASE_SCHEMA_REFERENCE.md`), and configuring a real Firebase project per environment — none scheduled. (Wiring the remaining fired events to push, and in-app delivery, are both now done — see Current Sprint above.)
- In-panel password management for admins (Sprint 11 set an admin's password via `tinker`/seeder only — no self-service UI) — no sprint owns this.
- A Filament Analytics resource/dashboard, and populating `analytics_events`' `ip`/`device`/`country` columns (would need request context threaded through every service call site) — surfaced by Sprint 12, neither scheduled.
- AI Host beyond v0: the full "Yowi" persona (voice, moderation, translation, recommendations), a `RoundCompleted` trigger, retry/backoff on failure, and configuring a real OpenAI project per environment — surfaced by Sprint 13, none scheduled.

Lower-priority, not blocking, carried over from Sprint 1:
- Schedule `clerk:sync-users` as an hourly self-heal job.
- Add a GitHub Actions workflow running Pint + Pest on every PR.

---

## Next Recommended Sprint

None scheduled — Badges & Achievements (the prior recommendation) has landed (see Current Sprint above). Remaining work is the unscheduled items above (needs product/design decisions, or external credentials this agent can't provide) and Tier 4 (`§G`, deferred pending a business trigger). See `.claude/NEXT_TASK.md` for the current candidate list.

---

## Overall Completion Percentage

A single number is misleading given the scope gap between the documented vision and the current build target — three reference points instead:

| Reference frame | Completion | Basis |
|---|---|---|
| **Pre-roadmap foundation** (Clerk auth, catalog, party create/like, wallet engine) | **~100%** of its own scope | This slice is finished, tested, and stable — no further work planned against it except the Sprint 1 exposure fix. |
| **`docs/implementation/IMPLEMENTATION_ORDER.md`** (14-sprint actionable plan to a complete, playable core product) | **14 of 14 sprints executed (100%)** | Sprints 1–14 done (Sprint 7 minus reward-granting, descoped per the user). The plan itself is complete; remaining work is unscheduled (see Current Priority). |
| **Full documented platform vision** (`docs/architecture/`, ~26 modules incl. Marketplace, Realtime, AI, Admin, Enterprise, Creator Economy) | **~42%** | 11 of ~26 modules fully built+exposed (Auth, Game Catalog, Party Likes, Wallet, Marketplace-purchase, Domain Events, Push token registration, Friends/social graph, Admin Panel v0, Analytics & Observability baseline, AI Host v0), 6 partial (incl. Game Engine, Realtime, and Notifications), ~9 with zero code. Weighted toward "exists and works," not toward doc page count. |

For context: `docs/architecture/60_PLATFORM_ROADMAP.md` claims "Phase 1: Foundation" is `Status: Completed` including Friends, Marketplace, Notifications, Realtime, and Voice — that claim does not hold against the code (see `docs/audit/ARCHITECTURE_GAP_ANALYSIS.md`). The figures above are the code-verified numbers; treat any completion claim inside `docs/architecture/` as aspirational framing, not status.
