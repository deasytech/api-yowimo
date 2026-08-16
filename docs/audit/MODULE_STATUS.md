# Module Status — Yowimo Backend

**Audit date:** 2026-08-14

Status of every module named in `docs/architecture/` against what actually exists in code, as of `dev`@`1f81022`. Modules are grouped the way the architecture docs group them (see `02_SYSTEM_ARCHITECTURE.md`, `22_BACKEND_SERVICE_CATALOG.md`, `60_PLATFORM_ROADMAP.md`).

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
| **Friends / Social Graph** | ⬜ Not started | No `friends` table, model, controller, or route. Extensively specified in docs 02–07, 22, 39, 41, 43–47. |
| **Party (create/discover)** | 🟡 Partial | `index`/`store`/`show` only. Room-code generation, visibility rules, draft/scheduled/live status derivation all real. |
| **Party lifecycle (join/leave/start/end/players)** | ✅ Built | `party_members` table + `PartyMembershipService`; `POST/DELETE /parties/{id}/join,leave`, host-only `POST /parties/{id}/start,end`. `players_count` wired to real membership counts. Host auto-joins on party creation and cannot leave (must end instead). |
| **Party Likes** | ✅ Built | Full like/unlike with idempotent counters, tested. |
| **Game Catalog (GameType, Pack, PackCard)** | ✅ Built | Full read API, filtering, search, cursor pagination, featured packs, preview cards. No write API (content is seed-managed). |
| **Game Engine (rounds/turns/timers/votes/challenges)** | 🟡 Partial (Sprint 7 landed) | `game_sessions`/`rounds`/`turns` tables + `GameSessionService`; host-only `POST /parties/{id}/game/start` and `POST /game/{id}/next-turn` deal Truth/Dare cards and advance a poll-driven state machine (randomized turn order, host-configurable rounds, auto-completion). Sprint 7 added a 30s server-authoritative turn timer with AFK-skip (tracked per turn via `turns.is_afk`), a crash-recovery sweep (`game:sweep-expired-turns`), and `RoundCompleted`/`GameCompleted` domain events. Still missing: scoring, votes, and reward granting — rewards were explicitly descoped from Sprint 7 per the user and have no owning sprint in `IMPLEMENTATION_ORDER.md`. |
| **Wallet (ledger engine)** | ✅ Built | `WalletService` is the most mature code in the repo — transactional, row-locked, idempotent credit/debit/recalculate, append-only ledger enforced at model + DB level. Now reachable via the read API below. |
| **Wallet API (balance/history/topup)** | 🟡 Partial | `GET /wallet` and `GET /wallet/transactions` (cursor-paginated, `WalletPolicy`-guarded) ship real balance/currency/ledger data. `UserResource` no longer stubs `wallet.enabled: false`. No top-up/purchase (write) endpoint yet — see Sprint 2. |
| **Token Bundles (catalog + purchase)** | 🟡 Partial | `GET /token-bundles` list + `POST /token-bundles/{id}/purchase` (credits the wallet via `PurchaseService`/`WalletService::credit()`, idempotency-key enforced). No `show` endpoint. |
| **Marketplace (purchase flow, inventory, ownership)** | 🟡 Partial | Token bundle purchase (top-up, `PurchaseService`) and pack purchase (spend, `PackPurchaseService` + `pack_purchases` table, gates full `PackCard` content behind ownership) both exist. Token top-up uses a manual/test `PaymentProvider` — no real payment gateway yet. |
| **Realtime (Reverb / channels / presence)** | ⬜ Not started | No `config/broadcasting.php`, no Reverb package, `BROADCAST_CONNECTION=log`. No channel classes anywhere. |
| **AI Host ("Yowi")** | ⬜ Not started | No AI SDK installed, no provider abstraction, no prompt registry. Entire module (`11`, `26`, `48`) is speculative. |
| **Notifications (push/in-app/email)** | ⬜ Not started | No `Notifications`/`Mail` directories, no FCM/APNs/SES wiring, `User` uses Laravel's `Notifiable` trait but nothing sends anything. |
| **Chat / Messaging** | ⬜ Not started | No tables, no code. |
| **Voice/Video (LiveKit)** | ⬜ Not started | No LiveKit SDK, no config, no code. |
| **Rewards / XP / Bonuses** | ⬜ Not started | `WalletTransactionType` enum has a `Bonus` case, but nothing computes or grants rewards. |
| **Sponsorship** | 🟡 Partial (schema hint only) | `parties.is_sponsored`/`sponsor_name` columns exist on the `parties` table; no sponsor entity, no sponsor-facing anything. |
| **Referrals** | ⬜ Not started | No tables, no code. |
| **Admin Panel** | ⬜ Not started | No Filament, no Spatie Permissions, no admin routes/controllers. `HorizonServiceProvider` gate is the only "admin" surface, and it's `local`-only. |
| **Moderation / Trust & Safety** | ⬜ Not started | No reports table, no moderation pipeline, no trust score. |
| **Analytics / Observability** | ⬜ Not started | No analytics-events table, no `/health` beyond Laravel's default `/up`, no Sentry/Prometheus/Grafana wiring. |
| **Creator Economy** | ⬜ Not started | No creator entity, payout, or revenue-share logic anywhere. |
| **Corporate / Enterprise / Multi-Tenant** | ⬜ Not started | No `tenant_id`/`organization_id` on any table. No org/workspace/department entities. |
| **Internationalization / Localization** | ⬜ Not started | `APP_LOCALE`/`APP_FALLBACK_LOCALE` are Laravel defaults; no translation tables, no locale-aware content pipeline. |
| **CI/CD Pipeline** | ⬜ Not started | No `.github/workflows`. Docs (`18`, `51`) specify a full GitHub Actions blue-green pipeline. |
| **Infrastructure-as-code / Docker / K8s** | ⬜ Not started | No Dockerfile, no IaC found in the repo. |

---

## Roll-up

Of the ~26 modules the architecture docs treat as first-class platform components, **3 are fully built and exposed** (Auth, Game Catalog, Party Likes), **1 substantial engine is built but entirely unexposed** (Wallet), **4 are partial slices** (Profile, Party create/discover, Token Bundle catalog, Sponsorship schema hint), and **the remaining ~18 do not exist in any form** — no migration, no model, no route, no config.

This directly contradicts `docs/architecture/60_PLATFORM_ROADMAP.md`, which marks "Phase 1: Foundation" as **Status: Completed** and lists Authentication, Profiles, Friends, Party System, Wallet, Marketplace, Notifications, Realtime, Voice, and Infrastructure as done. Per the code, only Authentication and a read-only slice of Party/Wallet qualify; Friends, Marketplace, Notifications, Realtime, and Voice have zero code. See `ARCHITECTURE_GAP_ANALYSIS.md` for detail.
