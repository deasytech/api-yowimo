# Module Status — Yowimo Backend

**Audit date:** 2026-08-27

Status of every module named in `docs/architecture/` against what actually exists in code, as of `dev` after the post-Sprint-14 "consume friend-request events" work (Notifications + Realtime now consume `FriendRequestSent`/`FriendRequestAccepted`). Modules are grouped the way the architecture docs group them (see `02_SYSTEM_ARCHITECTURE.md`, `22_BACKEND_SERVICE_CATALOG.md`, `60_PLATFORM_ROADMAP.md`).

**Status key**

| Status | Meaning |
|---|---|
| ✅ Built | Implemented and exposed via API, with tests |
| 🟡 Partial | Implemented for a narrow slice only (e.g. read-only, no lifecycle) |
| 🔵 Built, unexposed | Real backend logic exists but no route reaches it |
| ⬜ Not started | No code, no migration, no route |
| 📄 Docs only | Extensively specified in `docs/architecture/` with no corresponding code at all |

---

| Module | Status | Evidence |
|---|---|---|
| **Authentication (Clerk)** | ✅ Built | Custom `clerk` guard, JWT verification w/ JWKS caching, JIT provisioning, webhook sync, backfill command. Fully tested. |
| **User Profiles** | 🟡 Partial | `GET/PATCH /users/me` only — view + edit own profile. No public profile view, no avatar upload, no account deletion. |
| **Friends / Social Graph** | ✅ Built | `friendships` table + `FriendshipService`; `POST/GET /friend-requests`, `POST /friend-requests/{id}/accept,reject`, `DELETE /friend-requests/{id}` (cancel), `GET /friends`, `DELETE /friends/{id}` (unfriend, soft `removed` status). `FriendRequestSent`/`FriendRequestAccepted` domain events dispatch and are now consumed by both Notifications (push) and Realtime (`App.Models.User.{id}` private channel). `blocked` status intentionally out of v0 scope. |
| **Party (create/discover)** | 🟡 Partial | `index`/`store`/`show` only. Room-code generation, visibility rules, draft/scheduled/live status derivation all real. |
| **Party lifecycle (join/leave/start/end/players)** | ✅ Built | `party_members` table + `PartyMembershipService`; `POST/DELETE /parties/{id}/join,leave`, host-only `POST /parties/{id}/start,end`. `players_count` wired to real membership counts. Host auto-joins on party creation and cannot leave (must end instead). |
| **Party Likes** | ✅ Built | Full like/unlike with idempotent counters, tested. |
| **Game Catalog (GameType, Pack, PackCard)** | ✅ Built | Full read API, filtering, search, cursor pagination, featured packs, preview cards. No write API (content is seed-managed). |
| **Game Engine (rounds/turns/timers/votes/challenges)** | 🟡 Partial (Sprint 7 landed) | `game_sessions`/`rounds`/`turns` tables + `GameSessionService`; host-only `POST /parties/{id}/game/start` and `POST /game/{id}/next-turn` deal Truth/Dare cards and advance a poll-driven state machine (randomized turn order, host-configurable rounds, auto-completion). Sprint 7 added a 30s server-authoritative turn timer with AFK-skip (tracked per turn via `turns.is_afk`), a crash-recovery sweep (`game:sweep-expired-turns`), and `RoundCompleted`/`GameCompleted` domain events. Still missing: scoring, votes, and reward granting — rewards were explicitly descoped from Sprint 7 per the user and have no owning sprint in `IMPLEMENTATION_ORDER.md`. |
| **Wallet (ledger engine)** | ✅ Built | `WalletService` is the most mature code in the repo — transactional, row-locked, idempotent credit/debit/recalculate, append-only ledger enforced at model + DB level. Now reachable via the read API below. |
| **Wallet API (balance/history/topup)** | 🟡 Partial | `GET /wallet` and `GET /wallet/transactions` (cursor-paginated, `WalletPolicy`-guarded) ship real balance/currency/ledger data. `UserResource` no longer stubs `wallet.enabled: false`. No top-up/purchase (write) endpoint yet — see Sprint 2. |
| **Token Bundles (catalog + purchase)** | 🟡 Partial | `GET /token-bundles` list + `POST /token-bundles/{id}/purchase` (credits the wallet via `PurchaseService`/`WalletService::credit()`, idempotency-key enforced). No `show` endpoint. |
| **Marketplace (purchase flow, inventory, ownership)** | 🟡 Partial | Token bundle purchase (top-up, `PurchaseService`) and pack purchase (spend, `PackPurchaseService` + `pack_purchases` table, gates full `PackCard` content behind ownership) both exist. Token top-up uses a manual/test `PaymentProvider` — no real payment gateway yet. |
| **Realtime (Reverb / channels / presence)** | 🟡 Partial | `laravel/reverb` installed; `party.{id}` presence channel (party lobby), `game-session.{id}` private channel (active game), and a per-user `App.Models.User.{id}` private channel, all membership/identity-gated. `PartyMemberJoined`, `PartyStarted`, `TurnStarted`, `RoundCompleted`, `GameCompleted`, `FriendRequestSent`, `FriendRequestAccepted` broadcast. Wallet/Purchase events and `PartyCreated` still aren't broadcast; `PartyMembershipService::leave()` still doesn't dispatch any event (pre-existing gap). No live client has verified the integration end-to-end. |
| **AI Host ("Yowi")** | ✅ Built (Sprint 13, 2026-08-27) | `App\Services\AI\AIProvider` interface + `OpenAiProvider` (lean `Http`-facade call, no SDK package); `SendAiHostMessage` listener (queued, off `GameCompleted`) broadcasts a playful AI-generated message via the new `AiHostMessageSent` event onto the existing `game-session.{id}` private channel; skip-silently-and-log on any OpenAI failure. No real OpenAI project/credentials configured in any environment yet (inert until `OPENAI_API_KEY` is set, same pattern as Firebase/Sentry). The full "Yowi" persona (voice, moderation, translation, recommendations) and a `RoundCompleted` trigger are intentionally out of v0 scope, per the user. |
| **Notifications (push/in-app/email)** | 🟡 Partial | `push_tokens` table/API, `kreait/laravel-firebase` FCM channel, 5 of 9 fired domain events (`PartyMemberJoined`, `RoundCompleted`, `WalletCredited`, `FriendRequestSent`, `FriendRequestAccepted`) trigger a queued push notification. No real Firebase project/credentials configured in any environment yet (inert until set), no in-app/email delivery, no APNs (FCM covers iOS too, so not needed). |
| **Chat / Messaging** | ⬜ Not started | No tables, no code. |
| **Voice/Video (LiveKit)** | ⬜ Not started | No LiveKit SDK, no config, no code. |
| **Rewards / XP / Bonuses** | ⬜ Not started | `WalletTransactionType` enum has a `Bonus` case, but nothing computes or grants rewards. |
| **Sponsorship** | 🟡 Partial (schema hint only) | `parties.is_sponsored`/`sponsor_name` columns exist on the `parties` table; no sponsor entity, no sponsor-facing anything. |
| **Referrals** | ⬜ Not started | No tables, no code. |
| **Admin Panel** | ✅ Built (Sprint 11, 2026-08-26) | `filament/filament` v5 panel at `/admin`, gated on a new `is_admin` boolean on `users`, separate password-based login on the existing `web` guard (independent of the API's `clerk` guard). `UserResource` (view/edit, no create/delete), `PartyResource`/`WalletTransactionResource` (view/audit only — no create/edit/delete registered), `GameTypeResource`/`PackResource`/`PackCardResource`/`TokenBundleResource` (full CRUD, the real write path for catalog content). `HorizonServiceProvider`'s `viewHorizon` gate now also allows `is_admin` users, additive to its existing `local`-only bypass. No in-panel password-management UI, no moderation/analytics/enterprise admin surface — deliberately out of v0 scope. |
| **Moderation / Trust & Safety** | ⬜ Not started | No reports table, no moderation pipeline, no trust score. |
| **Analytics / Observability** | ✅ Built (Sprint 12, 2026-08-27) | `analytics_events` table + `AnalyticsEvent` model; `RecordAnalyticsEvent` persists a row (replacing its prior `Log::info()`-only behavior) for all six Sprint 5 backbone events (`PartyCreated`, `PartyMemberJoined`, `PartyStarted`, `WalletCredited`, `WalletDebited`, `PurchaseCompleted`). `GET /api/v1/health` (public, unauthenticated) checks DB/Redis/Queue/Broadcast(Reverb) connectivity, 503 if any is down. `sentry/sentry-laravel` installed and wired via `Integration::handles()` in `bootstrap/app.php` — inert until `SENTRY_LARAVEL_DSN` is set (no project configured in any environment yet, same pattern as Firebase). No Analytics resource in the Filament admin panel (out of scope for this sprint), no Prometheus/Grafana. |
| **Creator Economy** | ⬜ Not started | No creator entity, payout, or revenue-share logic anywhere. |
| **Corporate / Enterprise / Multi-Tenant** | ⬜ Not started | No `tenant_id`/`organization_id` on any table. No org/workspace/department entities. |
| **Internationalization / Localization** | ⬜ Not started | `APP_LOCALE`/`APP_FALLBACK_LOCALE` are Laravel defaults; no translation tables, no locale-aware content pipeline. |
| **CI/CD Pipeline** | ⬜ Not started | No `.github/workflows`. Docs (`18`, `51`) specify a full GitHub Actions blue-green pipeline. |
| **Infrastructure-as-code / Docker / K8s** | ⬜ Not started | No Dockerfile, no IaC found in the repo. |

---

## Roll-up

Of the 28 modules listed above, **8 are fully built and exposed** (Auth, Friends/Social Graph, Party lifecycle, Party Likes, Game Catalog, Admin Panel, Analytics & Observability, AI Host v0), **1 substantial engine is built but only partially exposed** via a separate API layer (Wallet ledger — see Wallet API), **9 are partial slices** (User Profiles, Party create/discover, Game Engine, Wallet API, Token Bundles, Marketplace, Realtime, Notifications, Sponsorship schema hint), and **the remaining 10 do not exist in any form** — no migration, no model, no route, no config.

This directly contradicts `docs/architecture/60_PLATFORM_ROADMAP.md`, which marks "Phase 1: Foundation" as **Status: Completed** and lists Authentication, Profiles, Friends, Party System, Wallet, Marketplace, Notifications, Realtime, Voice, and Infrastructure as done. Per the code: Authentication is genuinely done; Profiles, Party System, Wallet, Marketplace, Notifications, and Realtime are each only partial slices of their claimed scope (see the rows above); Friends and Voice have zero code. See `ARCHITECTURE_GAP_ANALYSIS.md` for detail.
