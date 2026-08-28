# Implementation Status — Yowimo Backend

**Assessed:** 2026-08-28, after Sprint 14 (Hardening pass) and five post-Sprint-14 items landed, by direct code inspection against `docs/architecture/`. Sprint 14 hardened existing endpoints (rate limits, security review, tests, backup/DR docs) without changing any module's status/scope below. The post-Sprint-14 items did change scope: `FriendRequestSent`/`FriendRequestAccepted` are now consumed by both Notifications (push) and Realtime (`App.Models.User.{id}` channel); `WalletCredited`/`WalletDebited`/`PurchaseCompleted`/`PartyCreated` now broadcast on that same channel alongside a new `PartyMemberLeft` event on the `party.{id}` channel; `PartyStarted`/`GameCompleted`/`PartyMemberLeft`/`WalletDebited`/`PurchaseCompleted` now also send push notifications; all 10 fired-and-notified events now additionally persist an in-app notification row (`notifications` table, `GET`/`PATCH /notifications/*`); and `GameCompleted` now also grants a flat 25-token wallet reward to every player who took a turn — see the Realtime, Notifications, and Game Engine rows below.
**Sources:** `docs/audit/*`, `docs/implementation/IMPLEMENTATION_ORDER.md`, `.claude/CURRENT_PHASE.md`.

**Status legend:** ✅ Complete · 🟡 Partial · 🔵 Built, unexposed · ⬜ Missing
**Priority legend:** Critical (do next) · High · Medium · Low · Deferred (no sprint scheduled — see `IMPLEMENTATION_ORDER.md` §G)

| Module | Status | Complete % | Needs Refactor? | Priority | Dependencies |
|---|---|---|---|---|---|
| Authentication (Clerk) | ✅ Complete | 100% | No | Maintain | — |
| Game/Pack/PackCard Catalog (read) | ✅ Complete | 90% (no write/admin API) | No | Low | Auth |
| Party Likes | ✅ Complete | 100% | No | Maintain | Party (create) |
| Party Create/Discover/Show/Membership/Lifecycle | ✅ Complete | 100% | No | Maintain | Auth, Game Catalog |
| User Profile | 🟡 Partial | 40% (view/edit only) | No — extend only | Low | Auth |
| Token Bundles (catalog + purchase) | 🟡 Partial | 80% (list + purchase; no `show`, no real payment gateway) | No — extend only | Low | Wallet API |
| Sponsorship | 🟡 Partial | 10% (schema columns only) | No | Deferred | Party |
| Horizon / Queue | 🟡 Partial | ~60% (queue processing proven via a real worker since Sprint 5 — `RecordAnalyticsEvent`, the turn-timer job, notification jobs; `viewHorizon` gate now also allows `is_admin` users, in addition to the `local`-only bypass, since Sprint 11; but `laravel/horizon` itself is only installed/configured, never actually started by any process here, so it's unverified as active) | Yes — small (run `php artisan horizon` instead of `queue:listen` if its features are wanted) | Maintain | — |
| Wallet Ledger Engine | ✅ Complete | 100% internal / exposed via read API | No | Maintain | Auth |
| Wallet API (routes/controller) | ✅ Complete | 100% read + token-bundle top-up write path | No | Maintain | Wallet Ledger Engine |
| CI/CD Pipeline | ⬜ Missing | 0% | N/A — net new (infra) | High (carried over from Sprint 1) | — |
| Marketplace (purchase/inventory) | ✅ Complete | Token bundle purchase (top-up) + pack purchase/inventory/ownership both done | No | Maintain | Wallet API, Token Bundles, Pack Catalog |
| Domain Events & Listeners | ✅ Complete | 100% | No | Maintain | Existing services (Wallet, Party, PartyMembership, Purchases) — retrofitted |
| Game Engine (rounds/turns/timers/scoring) | 🟡 Partial | ~75% (rounds/turns/timers/AFK/completion-events done; flat 25-token reward to every turn-taker on `GameCompleted` done; votes/scoring/XP/badges/streaks/combo multipliers missing — full Reward/Scoring/Achievement Engine unscheduled) | No — extend only | Maintain (remaining scope unscheduled) | Party Lifecycle, Pack Catalog, Domain Events |
| Realtime (Reverb) | 🟡 Partial | ~95% (Reverb installed; party lobby presence channel + game session private channel + per-user `App.Models.User.{id}` private channel; `PartyMemberJoined`/`PartyMemberLeft`/`PartyStarted`/`TurnStarted`/`RoundCompleted`/`GameCompleted`/`FriendRequestSent`/`FriendRequestAccepted`/`WalletCredited`/`WalletDebited`/`PurchaseCompleted`/`PartyCreated` all broadcast) | No — extend only | Maintain | Domain Events, Game Engine |
| Notifications | 🟡 Partial | ~90% (push-token registration, FCM channel, in-app `InAppChannel`/`notifications` table/`GET`+`PATCH /notifications/*`; 10 of 12 fired events wired to both channels — all except `PartyCreated`, deliberately skipped as self-triggered, and `TurnStarted`, deliberately skipped as too frequent; no real Firebase project configured yet, push inert until `FIREBASE_CREDENTIALS` is set; no `notification_preferences`/per-channel opt-out) | No — extend only | Maintain (remaining scope unscheduled) | Domain Events, Queue activation |
| Friends / Social Graph | ✅ Complete | 100% (v0 scope: send/accept/reject/cancel/unfriend, list friends/pending) | No | Maintain | Auth (Users) |
| Admin Panel | ✅ Complete | 100% (v0 scope: Filament panel, `is_admin`-gated, separate password login; Users view/edit, Parties/Wallet view-only, catalog full CRUD) | No | Maintain (no in-panel password management yet — unscheduled) | Users, Parties, Wallet, Catalog (data to administer) |
| Analytics / Observability | ✅ Complete | 100% (v0 scope: `analytics_events` persistence for all 6 backbone events, `/health` for DB/Redis/Queue/Broadcast, Sentry wired but inert) | No | Maintain (no Analytics admin resource yet — unscheduled) | Domain Events |
| AI Host ("Yowi") | ✅ Complete | 100% (v0 scope: `AIProvider`/`OpenAiProvider`, `GameCompleted`-triggered playful message broadcast via `AiHostMessageSent`) | No | Maintain (remaining "Yowi" persona scope unscheduled) | Domain Events, Realtime |
| Chat / Messaging | ⬜ Missing | 0% | N/A — net new | Deferred | Friends, Realtime |
| Voice/Video (LiveKit) | ⬜ Missing | 0% | N/A — net new | Deferred | Realtime |
| Moderation / Trust & Safety | ⬜ Missing | 0% | N/A — net new | Deferred | Chat, Friends |
| Creator Economy | ⬜ Missing | 0% | N/A — net new | Deferred | Marketplace |
| Corporate / Multi-Tenant / Enterprise | ⬜ Missing | 0% | N/A — net new (schema-wide `tenant_id` retrofit) | Deferred | Admin, all core modules |
| Internationalization | ⬜ Missing | 0% | N/A — net new | Deferred | — |

---

## Notes

- **"Needs Refactor?"** answers whether *existing* code must change. It's `N/A` for modules with zero code today — those need net-new construction, not refactoring. The Horizon gate's admin extension landed in Sprint 11; actually running `php artisan horizon` (queue activation) is the one remaining refactor-level touch-up on that module.
- **No module needs a rewrite.** Every "Needs Refactor?: Yes" item is a small, isolated, additive fix — consistent with `docs/audit/TECHNICAL_DEBT.md`'s finding that nothing shipped so far is broken, only incomplete or unexposed.
- **Complete % is per-module scope**, not weighted by lines of code or doc page count — e.g. Game Catalog is 90% because only an admin/write path is missing, while Wallet Ledger is 100% internally complete but contributes 0% of its value until the API layer exists (tracked as a separate row).
- Full per-module evidence: `docs/audit/MODULE_STATUS.md`. Full dependency rationale and sprint sequencing: `docs/implementation/IMPLEMENTATION_ORDER.md`.
