# Project Context — Yowimo Backend

**For:** any engineer (or AI assistant) new to this repository.
**Last verified:** 2026-07-13, against `dev`@`bd4d056`, by direct code inspection.
**Companion reading:** `docs/architecture/` (61-file vision spec), `docs/audit/` (this codebase's actual state vs. that vision), `docs/implementation/IMPLEMENTATION_ORDER.md` (the recommended build sequence).

Read this file first. It tells you what's real. `docs/architecture/` tells you what's planned. Don't assume a class, table, or endpoint described in `docs/architecture/` exists until you've checked — see "Current Implementation Status" below and `docs/audit/MODULE_STATUS.md` for the verified per-module truth.

---

## 1. Project overview

Yowimo is a social multiplayer party-game platform: users host and join real-time party sessions built around truth/dare-style card packs, with a token economy, a game catalog, and (per the documented vision, not yet built) AI-hosted gameplay, marketplace commerce, and eventually corporate/enterprise events. The repository at hand is the **Laravel API backend**. A separate React Native/Expo mobile app is the primary client (see §8); this repo has no meaningful web frontend of its own.

The project is early-stage: the actual shipped code is a disciplined "Phase 1" slice (auth, catalog, party creation, a wallet ledger engine), while an extensive 61-document architecture corpus describes a much larger, later-stage platform. That gap is intentional to understand, not a sign of neglect — see §7 and §11.

---

## 2. Product vision

Full vision: `docs/architecture/01_PRODUCT_VISION.md`. Summary, per that document's seven "pillars":

1. **Party Platform** — hosting/joining real-time party sessions.
2. **Social Platform** — friends, profiles, social discovery.
3. **Game Platform** — the card-based game engine (truth/dare and beyond).
4. **Token Economy** — a wallet/ledger and purchasable token bundles.
5. **Marketplace** — buying card packs, cosmetics, and other content with tokens.
6. **AI Experiences** — an AI host persona ("Yowi") that narrates and moderates gameplay.
7. **Corporate Platform** — enterprise/team-building events, multi-tenant orgs.

The doc's own 6-phase roadmap (Phase 1: Core Platform → Phase 2: Realtime/Voice → Phase 3: AI → Phase 4: Creator Economy → Phase 5: Competitive Play → Phase 6: Enterprise) is the intended long arc. As of this writing, the codebase has *begun* Phase 1 (auth, catalog, party creation, wallet ledger) and has not reached full Phase 1 completion by the doc's own definition — see §7.

---

## 3. Tech stack

**Verified from `composer.json`, `.env.example`, and `config/` — this is what's actually installed, which is narrower than `docs/architecture/00_READ_ME_FIRST.md` and `49_ENVIRONMENT_VARIABLE_REFERENCE.md` describe:**

| Layer | Actual |
|---|---|
| Language | PHP 8.3 |
| Framework | Laravel `^13.8` |
| Auth | Custom Clerk JWT guard (`Auth::viaRequest('clerk', ...)`) — no Sanctum, no password auth, no sessions-based login |
| Database | MySQL |
| Cache / Queue | Redis; Laravel Horizon installed (dashboard gated to `local` env, no jobs dispatched yet) |
| Broadcasting | Not configured (`BROADCAST_CONNECTION=log`, no `laravel/reverb`, no `config/broadcasting.php`) |
| Webhooks | `svix/svix` (Clerk webhook signature verification) |
| JWT | `firebase/php-jwt` |
| Testing | Pest 4 + PHPUnit 12, `RefreshDatabase` bound globally |
| Frontend in this repo | None real — stock Laravel welcome view only |
| CI/CD | None (no `.github/workflows`) |

**Not installed, despite being specified in the docs:** Laravel Reverb, LiveKit/Agora/Twilio, any AI SDK (OpenAI/Anthropic/Gemini), any payment SDK (Stripe/Paystack), Spatie Permissions, Filament.

The mobile client (separate repo) is inferred to be React Native + Expo from `.env.example` CORS origins (`localhost:8081`, `localhost:19006`, `app.yomiwo.com`) and from `docs/architecture/23_FRONTEND_ARCHITECTURE.md` / `54_REACT_NATIVE_IMPLEMENTATION_GUIDE.md`.

---

## 4. Repository structure

```
app/
  Console/Commands/     — one command: clerk:sync-users (manual Clerk backfill)
  Enums/                — 8 backed string enums (PartyStatus, PackCategory, WalletTransactionType, ...)
  Exceptions/Api/       — 3 API-specific exceptions, centrally mapped to HTTP codes
  Http/
    Controllers/Api/V1/ — 7 thin controllers, each delegates to a Service
    Requests/Api/V1/    — Form Requests (authorize() always true; Policies do the real auth check)
    Resources/Api/V1/   — API Resource transformers
  Models/                — 10 Eloquent models
  Policies/              — 4 Policy classes
  Providers/             — AppServiceProvider (clerk guard + rate limiters), HorizonServiceProvider
  Services/              — 13 service classes; all business logic lives here
  Support/               — ApiResponse (response envelope), ApiExceptionRegistrar
database/
  migrations/            — 12 migrations (users/sessions/cache/jobs stock + 9 app tables)
  factories/, seeders/   — one factory per model, seeders for catalog content
routes/
  api.php                — 13 routes total, all under /api/v1, all Clerk-authenticated except the webhook
tests/
  Feature/               — 19 test files, 80 tests / 308 assertions, all passing
  Support/                — FakesClerk, FakesClerkWebhook test helpers
docs/
  architecture/           — 61-file aspirational engineering handbook (00–60) — the vision, not the current state
  audit/                  — this codebase's verified actual state vs. that vision (start here for "what's real")
  implementation/         — IMPLEMENTATION_ORDER.md and phase-tracking docs
  adr/, reports/          — placeholders, mostly unpopulated as of this writing
.claude/, .agents/, .cursor/ — AI assistant configuration and skills (Laravel Boost, Horizon, Pest, Tailwind skills)
AGENTS.md                 — this project's own engineering handbook / AI workflow instructions (root level)
```

Notably **absent** from `app/`, despite being the mandated structure in `AGENTS.md` and `docs/architecture/00_READ_ME_FIRST.md`: `Events`, `Listeners`, `Jobs`, `Notifications`, `Observers`, `Repositories`, `Rules`, `Traits`, `ValueObjects`, `Mail`. The current codebase uses a flat Controller → Service → Model pattern, not the repository/DTO/event-driven pattern the docs describe. See §10 and §11.

---

## 5. Architecture summary

**What's actually implemented is a straightforward layered API:**

```
Route (routes/api.php)
  → Controller (thin: authorize() via Policy, delegate to Service)
    → Service (all business logic, transactions, validation)
      → Eloquent Model
```

- No repository layer, no DTOs, no domain events/listeners, no jobs currently dispatch anything.
- Every write-heavy service wraps its critical section in `DB::transaction()` and handles race conditions explicitly (unique-constraint retries, row locks) rather than relying on optimistic assumptions — this is a consistent, deliberate pattern across `WalletService`, `PartyService`, `PartyLikeService`, and the Clerk provisioning services.
- API responses use one consistent envelope (`App\Support\ApiResponse`) and one centralized exception-to-HTTP-status mapping (`App\Support\ApiExceptionRegistrar`), registered in `bootstrap/app.php`.
- Auth is entirely delegated to Clerk: the Laravel app never handles passwords, only verifies a Clerk-issued JWT and JIT-provisions/updates a local `User` row from its claims (`ClerkUserProvisioner`). A parallel webhook path (`ClerkWebhookHandler`) keeps local users in sync on create/update/delete, idempotently (via `webhook_events`).

**What the docs describe but doesn't exist yet:** a modular/domain-oriented `app/Modules/{Domain}` layout, event-driven side effects, a repository layer, realtime broadcasting, and everything downstream of those (notifications, AI reactions, analytics feeds). See `docs/architecture/02_SYSTEM_ARCHITECTURE.md` for the target-state architecture and `docs/audit/ARCHITECTURE_GAP_ANALYSIS.md` §6–7 for the specific pattern mismatch.

---

## 6. Backend modules

| Module | Real code today | Vision doc |
|---|---|---|
| Authentication | Clerk JWT guard, JIT provisioning, webhook sync, backfill command | `06_SECURITY_STANDARDS.md` |
| User Profiles | View/edit own profile only | `03_DOMAIN_MODEL.md` |
| Game Catalog (GameType/Pack/PackCard) | Full read API, filtering, search, pagination; content is seed-managed, no write API | `27_CONTENT_AND_CARD_AUTHORING_PIPELINE.md` |
| Party | Create/discover/show/like only — no join/leave/start/end | `08_GAME_ENGINE.md`, `45_SEQUENCE_DIAGRAMS.md` |
| Wallet | Full ledger engine (`WalletService`) — transactional, idempotent, race-safe, append-only — but **zero HTTP routes reach it** | `12_WALLET_AND_TOKEN_SYSTEM.md` |
| Token Bundles | List only, no purchase endpoint | `13_MARKETPLACE_ARCHITECTURE.md` |
| Game Engine (rounds/turns/timers/votes) | Does not exist | `08_GAME_ENGINE.md` (marked CRITICAL there) |
| Realtime (Reverb) | Does not exist | `09_REALTIME_ARCHITECTURE.md` |
| AI Host ("Yowi") | Does not exist | `11_AI_HOST_ARCHITECTURE.md` |
| Marketplace (purchase/inventory) | Does not exist | `13_MARKETPLACE_ARCHITECTURE.md` |
| Notifications | Does not exist | `14_NOTIFICATION_SYSTEM.md` |
| Friends | Does not exist | referenced throughout 02–07 |
| Admin Panel | Does not exist | `16_ADMIN_PANEL_ARCHITECTURE.md` |
| Moderation | Does not exist | `17_MODERATION_AND_SAFETY.md` |
| Corporate/Multi-Tenant | Does not exist | `28_CORPORATE_PLATFORM_ARCHITECTURE.md`, `30_MULTI_TENANT_ENTERPRISE_ARCHITECTURE.md` |
| Creator Economy | Does not exist | `29_CREATOR_ECONOMY_AND_MARKETPLACE.md` |

Full verified table with evidence: `docs/audit/MODULE_STATUS.md`.

---

## 7. Current implementation status

- **13 API routes**, all under `/api/v1`, all `auth:clerk` + rate-limited except the Clerk webhook.
- **80 tests, 308 assertions, all passing.** Coverage is proportionate to what exists — everything real is well-tested.
- **Git history** shows a clean progression: Phase 0 (Clerk auth foundation) → Phase 1 (catalog/parties API + wallet ledger) → a pivot to writing the 61-file documentation corpus. There is no commit implementing a game engine, realtime, AI, marketplace purchases, or an admin panel.
- **`docs/architecture/60_PLATFORM_ROADMAP.md` marks "Phase 1: Foundation" as `Status: Completed`**, listing Authentication, Profiles, Friends, Party System, Wallet, Marketplace, Notifications, Realtime, Voice, and Infrastructure as done. **This is not accurate against the code.** Only Authentication is fully true; Friends, Marketplace, Notifications, Realtime, and Voice have zero code; Profiles/Party/Wallet are partial or built-but-unexposed. Treat any "completed" claim in `docs/architecture/` as aspirational framing, not a verified status — always check `docs/audit/` or the code itself.

For the detailed, evidence-backed breakdown: `docs/audit/CURRENT_STATE.md` (what exists), `docs/audit/MODULE_STATUS.md` (per-module verdict), `docs/audit/ARCHITECTURE_GAP_ANALYSIS.md` (why the docs and code diverge), `docs/audit/TECHNICAL_DEBT.md` (specific defects/gaps to close).

---

## 8. Mobile application relationship

This repository is **API-only**. There is no bundled mobile app code here. Evidence of the relationship:

- `.env.example` CORS origins target Expo dev ports (`localhost:8081`, `localhost:19006`) and a production mobile-facing domain (`app.yomiwo.com`), not a browser SPA.
- `docs/architecture/23_FRONTEND_ARCHITECTURE.md` and `54_REACT_NATIVE_IMPLEMENTATION_GUIDE.md` specify the client stack: React Native (Expo), TypeScript, NativeWind, React Query, Zustand, Expo Router.
- All API responses use a stable resource-transformer shape (`app/Http/Resources/Api/V1/`) intended for that client — note `UserResource` currently ships a **stale hardcoded wallet stub** (`wallet.enabled: false`) that predates the real wallet implementation; a mobile client reading that field today is told wallets don't exist (see `docs/audit/TECHNICAL_DEBT.md` #1).
- `docs/architecture/25_API_SDK_AND_CLIENT_LIBRARY.md` describes a shared typed SDK layer intended to sit between the mobile app and this API; there's no evidence that SDK exists yet (it would live in the mobile repo, not here).

**If you're working from the mobile side:** don't assume an endpoint exists because it's in `docs/architecture/39_REST_API_REFERENCE.md` — cross-check against the actual 13 routes in `routes/api.php` first.

---

## 9. Admin panel relationship

**No admin panel exists in this repository or anywhere in the stack today.** `docs/architecture/16_ADMIN_PANEL_ARCHITECTURE.md` and `55_ADMIN_PANEL_IMPLEMENTATION_GUIDE.md` specify a Filament 4 + Livewire admin app (roles, audit logging, content/wallet/user management) that would likely live as a separate panel within this same Laravel app (Filament typically installs into the host app) or a dedicated repo — the docs don't fully resolve which. Right now:

- Content (game types, packs, cards, token bundles) is **seed-managed only** — there is no write API and no admin UI, so changing catalog content today means editing `database/seeders/` and re-running them.
- The only "admin" surface that exists is the Horizon dashboard gate (`HorizonServiceProvider`), and it's restricted to the `local` environment — nobody can view queue/job status outside local dev, though this is currently moot since no jobs are dispatched yet.
- `docs/implementation/IMPLEMENTATION_ORDER.md` (Sprint 11) proposes introducing Filament specifically to give content and wallet-audit a real write/read path, once higher-priority core-loop work (marketplace, party lifecycle, game engine) lands first.

---

## 10. Coding standards

Authoritative sources: `AGENTS.md` (root, this project's own handbook + Laravel Boost guidelines) and `docs/architecture/21_CODING_STANDARDS_AND_BEST_PRACTICES.md` / `53_BACKEND_IMPLEMENTATION_GUIDE.md` (aspirational, and inconsistent with each other on some specifics — see §11). What's actually enforced/followed in this codebase today:

- PHP 8.3, PHP 8 constructor property promotion, explicit return types and param type hints everywhere.
- PHP 8 `#[Fillable]` attributes on models (not the legacy `$fillable` property) — follow this convention for new models.
- Controllers are thin: `authorize()` via a Policy, then delegate to a Service. All business logic lives in `app/Services/`.
- Form Requests handle validation only; `authorize()` on every Form Request currently returns `true` — real authorization happens via Policies in the controller, not in the Request. Follow this split, don't put `Gate`/`Policy` checks inside Form Requests.
- Every write path that touches money or a uniqueness constraint wraps in `DB::transaction()` and explicitly handles the race (unique-violation catch-and-retry, `lockForUpdate()`) rather than assuming single-writer safety. Follow this pattern for any new financial or uniqueness-sensitive code.
- One response envelope (`ApiResponse`) and one exception-mapping point (`ApiExceptionRegistrar`) — don't invent a second JSON shape or scatter `try/catch` HTTP-status logic into controllers.
- Tests: Pest 4, `Feature` tests are the default (per Laravel Boost guidance, "most tests should be feature tests"), `RefreshDatabase` globally bound. Use existing factories; check for factory states before adding new ones. Run `vendor/bin/pint --dirty --format agent` after any PHP change; do not delete tests without approval.
- **Do not create new base folders under `app/` without approval** — this is an explicit Laravel Boost rule in `AGENTS.md`. If a task seems to require `app/Events` or `app/Jobs`, that's a signal to confirm scope with the user/team first, since it's a structural change beyond the current pattern (see `docs/implementation/IMPLEMENTATION_ORDER.md` Sprint 5 for when that's planned to happen deliberately).
- Documentation files should only be created when explicitly requested (`AGENTS.md` rule) — this file and its companions in `docs/audit/`/`docs/implementation/` are the explicitly-requested exception.

---

## 11. Important architectural decisions

- **Auth is 100% delegated to Clerk.** No password column exists on `users`. The Laravel app's only auth responsibilities are: verify a Clerk JWT via cached JWKS, JIT-provision/update a local `User` from claims, and stay in sync via signed Clerk webhooks (idempotent via `webhook_events`). This is a deliberate, consistently-applied decision, not a partial migration.
- **Wallet balance is a cache; the ledger is the source of truth.** `wallets.balance` is explicitly documented (in code comments and the DB migration) as derived; `WalletTransaction` rows are append-only, enforced at three layers simultaneously: no `updated_at` column exists, Eloquent model hooks throw on `updating`/`deleting`, and the DB foreign key is `restrictOnDelete`. This exact principle ("balances NEVER edited directly") is repeated across at least 8 architecture docs (03, 04, 06, 12, 22, 38, 41, 52) and is the single most consistently-specified invariant in the whole documentation corpus — treat any code that would write to `balance` directly, outside `WalletService`, as a bug.
- **No repository layer, despite the docs mandating one.** The codebase uses Controller → Service → Model directly. This is a live disagreement with `docs/architecture/00/21/22/53`, not an oversight — see `docs/audit/ARCHITECTURE_GAP_ANALYSIS.md` §6 for the reasoning either to formalize this as the actual standard or to deliberately introduce repositories later; don't silently mix both patterns in new code.
- **No domain events/listeners exist yet**, despite `docs/architecture/07_EVENT_CATALOG.md` and `41_DOMAIN_EVENT_CATALOG.md` specifying an event-driven architecture as a core principle. Side effects (e.g., `likes_count` increments, `last_seen_at` throttling) happen inline inside services today. `docs/implementation/IMPLEMENTATION_ORDER.md` treats introducing this backbone as the single highest-leverage next infrastructure investment, since Realtime/Notifications/Analytics/AI all depend on it.
- **The documentation corpus was written after the code, in three batches** (docs 00–20, 21–37, 38–60, per their own closing statements), not incrementally alongside development. Git history confirms the doc-generation commits come after the last feature commit. Treat `docs/architecture/` as a target specification to build toward, cross-checked against `docs/audit/` for current truth — not as a changelog of what's been built.
- **The documentation set contains internal inconsistencies** from being authored in separate batches: Laravel 12 vs. 13, PostgreSQL vs. MySQL, differing coverage targets (95% vs. 100%), differing RTO figures, differing controller line-length limits, React Navigation vs. Expo Router. The actual code settles these where it can (Laravel 13, PHP 8.3, MySQL are real) — see `docs/audit/ARCHITECTURE_GAP_ANALYSIS.md` §9 for the full list.

---

## 12. Known limitations

(See `docs/audit/TECHNICAL_DEBT.md` for the full, evidence-backed list; summarized here.)

- **Wallet has no API.** The most mature backend code in the repo is unreachable by any client.
- **`UserResource` lies about wallet status** via a stale hardcoded stub that predates the real wallet implementation.
- **Parties can be created and viewed but never played** — no join/leave/start/end, no membership table; `parties.players_count` is a dangling, never-incremented column.
- **No game engine** — the platform's stated core loop (rounds, turns, timers, scoring) doesn't exist.
- **No commerce path** — token bundles and packs are listings only; nothing connects a purchase to the wallet ledger.
- **Horizon is installed but inert** — no job is ever dispatched to any queue yet, and its dashboard gate is `local`-only.
- **No CI/CD** — Pint/Pest are run manually; nothing enforces them pre-merge.
- **No realtime, AI, notifications, admin, moderation, friends, or multi-tenant code** — all fully absent, not partial.

---

## 13. Future roadmap

Two complementary sources, at different altitudes:

- **Long-term vision:** `docs/architecture/37_TECHNICAL_ROADMAP_AND_FUTURE_VISION.md` (5-generation AI roadmap, growth phases to 100M users, engineering team growth curve) and `docs/architecture/60_PLATFORM_ROADMAP.md` (10 platform phases from Foundation through AR/VR/Emerging Tech). These are multi-year, aspirational, and should be read as direction, not a near-term commitment.
- **Actionable near-term plan:** `docs/implementation/IMPLEMENTATION_ORDER.md` — a 14-sprint (roughly one engineer-quarter) plan sequencing the codebase from its current state toward a complete, playable, monetizable core product: wallet exposure → commerce (token/pack purchase) → party membership/lifecycle → a domain-events backbone → the game engine (REST-first, then realtime) → notifications and friends → admin and analytics → a narrowly-scoped first AI feature → a hardening pass. It explicitly defers Voice/Video, Moderation-at-scale, Creator Economy, Corporate/Multi-Tenant, and Internationalization until there's a concrete business signal to justify them, rather than building speculative enterprise scope now.

If you're picking up work on this project: start with `docs/implementation/IMPLEMENTATION_ORDER.md` to see what's next and why, not with the `docs/architecture/` roadmap docs, which describe the destination, not the path.
