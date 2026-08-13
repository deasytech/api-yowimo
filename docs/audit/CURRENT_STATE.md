# Current State — Yowimo Backend

**Audit date:** 2026-07-13
**Branch:** `dev` @ `bd4d056`
**Method:** Direct inspection of `app/`, `database/`, `routes/`, `tests/`, `config/`, `composer.json`, `.env.example`, and `git log`. No code was modified to produce this document.

This document is a factual snapshot of what actually exists in the repository right now. It does not compare against the `docs/architecture/` vision — see `ARCHITECTURE_GAP_ANALYSIS.md` for that comparison, and `MODULE_STATUS.md` for a per-module verdict.

---

## 1. Stack (actual, verified)

| Layer | Actual |
|---|---|
| Language / runtime | PHP 8.3 |
| Framework | Laravel `^13.8` (resolved 13.19.0) |
| Auth | Custom Clerk JWT guard (`Auth::viaRequest('clerk', ...)`) — no Sanctum, no password auth |
| Database | MySQL (`.env`: `yowimo_db`); migrations are DB-agnostic Laravel schema builder |
| Cache / Queue | Redis (`.env`: `CACHE_STORE=redis`, `QUEUE_CONNECTION=redis`); `config/queue.php` defaults to `database` if unset |
| Queue dashboard | Laravel Horizon `^5.47` installed; `viewHorizon` gate is `local`-only, no jobs are ever dispatched to any queue |
| Broadcasting | `BROADCAST_CONNECTION=log` in `.env`; no `config/broadcasting.php`, no `laravel/reverb`, no `pusher/pusher-php-server` |
| Webhook verification | `svix/svix ^1.96` (Clerk webhooks) |
| JWT | `firebase/php-jwt ^7.1` |
| Testing | Pest 4 (`pestphp/pest ^4.7`) + PHPUnit 12, `RefreshDatabase` globally bound |
| Frontend in this repo | None — `resources/views/welcome.blade.php` is the stock Laravel welcome page; `resources/js/app.js` / `resources/css/app.css` are Vite defaults. The real client is a separate React Native/Expo app (inferred from CORS origins `localhost:8081`, `localhost:19006`, `app.yomiwo.com` in `.env`) |
| CI/CD | None — no `.github/workflows` directory exists |

No packages for: Reverb, LiveKit/Agora/Twilio, OpenAI/Anthropic/Gemini SDKs, Stripe/PayPal/Paystack, Spatie Permissions, Filament.

---

## 2. Database schema (12 migrations, all applied in this order)

| Table | Purpose | Notable design |
|---|---|---|
| `users` | Local user record | `clerk_user_id` unique, no password/remember_token columns (auth is 100% delegated to Clerk); soft deletes |
| `sessions`, `cache`, `jobs` | Stock Laravel tables | Unmodified defaults |
| `webhook_events` | Clerk webhook idempotency ledger | `event_id` unique |
| `game_types` | Catalog of game categories | slug unique, `intensity` enum, active/sort flags |
| `packs` | Card packs, belongs to a `game_type` | slug unique, category, price, featured/active flags |
| `pack_cards` | Individual truth/dare cards in a pack | `kind` enum, `position`, `is_preview` |
| `token_bundles` | Purchasable token SKUs (catalog only) | slug unique, price as `decimal(8,2)` |
| `parties` | A hosted game session/room | `room_code` unique(6), `mode`/`visibility`/`status` enums, soft deletes, `players_count`/`likes_count` counters |
| `party_likes` | Party like/favorite | unique `(party_id, user_id)` |
| `wallets` | One wallet per user | `balance` is an explicitly-documented **cache**, not source of truth; unique `user_id` |
| `wallet_transactions` | Append-only ledger | no `updated_at` column at all; `idempotency_key` unique; polymorphic `reference`; FK to wallet is `restrictOnDelete` |

**Absent:** any table for game rounds/turns/timers/votes, marketplace purchases/inventory, notifications, chat/messages, friends, admin roles/audit logs, organizations/tenants, AI sessions, media/uploads, sponsors, referrals, moderation reports.

---

## 3. Models (`app/Models/`, 10 files)

`GameType`, `Pack`, `PackCard`, `Party` (+`SoftDeletes`), `PartyLike`, `TokenBundle`, `User` (+`SoftDeletes`, `Notifiable`, no password), `Wallet`, `WalletTransaction`, `WebhookEvent`. All use PHP 8 `#[Fillable]` attributes and typed casts (including backed enums). No `Events`, `Listeners`, `Jobs`, `Notifications`, `Observers`, `Repositories`, `Rules`, `Traits`, `ValueObjects`, or `Mail` directories exist anywhere under `app/` — despite these being the mandated structure in the project's own `AGENTS.md` and `docs/architecture/00_READ_ME_FIRST.md`.

Two models carry real invariants worth flagging as intentional design, not oversights:
- `Wallet::ledgerBalance()` recomputes `SUM(amount)` from `wallet_transactions` on demand — the `balance` column is a cache only.
- `WalletTransaction::booted()` registers `updating`/`deleting` hooks that throw `LogicException`, enforcing append-only at the Eloquent layer (in addition to the DB not having an `updated_at` column and `restrictOnDelete` at the FK level).

---

## 4. HTTP surface (`routes/api.php`) — 13 API routes total

```
GET|HEAD  api/v1/game-types
GET|HEAD  api/v1/packs
GET|HEAD  api/v1/packs/featured
GET|HEAD  api/v1/packs/{id}
GET|HEAD  api/v1/parties
POST      api/v1/parties
GET|HEAD  api/v1/parties/{id}
POST      api/v1/parties/{party}/like
DELETE    api/v1/parties/{party}/like
GET|HEAD  api/v1/token-bundles
GET|HEAD  api/v1/users/me
PATCH     api/v1/users/me
POST      api/v1/webhooks/clerk
```

All routes except the webhook require `auth:clerk` + `throttle:api` (60/min per user/IP). The webhook route uses `throttle:webhooks` (120/min per IP) and no auth, which is correct for a Svix-signed inbound webhook.

**No routes exist for:** wallet balance/history, token purchase/checkout, party update/delete/join/leave/start/end, friends, chat, notifications, AI, marketplace purchases, admin, organizations. `WalletService` is fully implemented and tested but is reachable from nowhere in the HTTP layer.

---

## 5. Services (`app/Services/`, 13 files)

| Service | Depth |
|---|---|
| `Wallet/WalletService` | Substantial — transactional, row-locked, idempotent, race-safe credit/debit/recalculate. The most mature code in the repo. Unreachable via API. |
| `Parties/PartyService` | Real logic — cursor-paginated discovery feed, transactional create with room-code collision retry, `viewer_has_liked` N+1 avoidance. No join/leave/lifecycle transitions. |
| `Parties/PartyLikeService` | Real — transactional like/unlike with floor-guarded counters. |
| `Parties/RoomCodeGenerator` | Real — ambiguous-character-excluded 6-char code generation with collision check against soft-deleted rows too. |
| `Clerk/*` (5 classes) | Real — JWKS fetch/cache/verify, Svix signature verification, JIT user provisioning with race handling, webhook idempotency, backfill sync shared with the console command. |
| `GameTypeService`, `PackService` | Real query-building/filtering, read-only. |
| `TokenBundleService`, `UserProfileService` | Thin — near-direct passthroughs to Eloquent. |

No repository layer, no DTOs, no service-to-service event dispatch anywhere. No TODOs, stubs, or commented-out code found in any service file.

---

## 6. Supporting layers

- **Requests:** 6 Form Request classes, all `authorize() => true` (authorization is delegated to Policies in controllers, not to the Form Request itself). Shared `HasCursorPagination` concern.
- **Resources:** 7 API Resource classes. `UserResource` hardcodes `'wallet' => ['enabled' => false, 'balance' => 0, 'currency' => 'points']` with an inline comment stating the wallet phase "isn't implemented yet" — stale, since `Wallet`/`WalletService` are in fact built (see Technical Debt).
- **Policies:** 4 classes (`GameTypePolicy`, `PackPolicy`, `PartyPolicy`, `TokenBundlePolicy`), all simple `is_active`/visibility checks.
- **Enums:** 8 backed string enums (`PartyStatus`, `PartyVisibility`, `PartyMode`, `PackCategory`, `PackCardKind`, `GameIntensity`, `UserStatus`, `WalletTransactionType`).
- **Exceptions:** 3 API-specific exceptions, centrally mapped to HTTP status codes in `App\Support\ApiExceptionRegistrar`.
- **Support:** `ApiResponse` gives one consistent success/paginated/error JSON envelope used everywhere.
- **Console:** one command, `clerk:sync-users`, a manual backfill tool — not scheduled anywhere in `routes/console.php`.

---

## 7. Tests

19 test files, **80 tests / 308 assertions, all passing** (`php artisan test --compact`). Coverage is proportionate to what exists: every controller, every service, the JWT verifier, the webhook handler, the room-code generator, and the wallet ledger (including concurrency/race and immutability-enforcement tests) all have dedicated feature tests. Two reusable test-support traits fake Clerk JWKS/JWT issuance and Svix webhook signing. Nothing tests wallet HTTP endpoints, purchases, admin, AI, or realtime — because none of that exists yet.

---

## 8. Documentation state

- `docs/architecture/` — 61 files (`00`–`60`), a complete aspirational engineering-handbook-style specification for a much larger platform. See `ARCHITECTURE_GAP_ANALYSIS.md`.
- `docs/audit/`, `docs/reports/`, `docs/adr/`, `docs/implementation/` — pre-existing empty placeholder files (0 bytes) from prior scaffolding, distinct from the four files this audit adds.
- `AGENTS.md` (repo root) — the project's own engineering handbook/workflow doc, prescribing the `app/{Events,Jobs,Listeners,...}` structure and an "audit before build" workflow.
- `FILE_STRUCTURE.md`, `Yowimo_Backend_Implementation_Guide.md` — supplementary root-level docs.

---

## 9. Git history signal

```
53d6c1d feat: Phase 1 catalog/parties API and wallet ledger foundation
5894ef1 feat: add clerk:sync-users command to backfill users from Clerk
584d6eb feat: Phase 0 backend foundation - users schema, Clerk webhook, infra
19a5d27 feat: Clerk JWT auth foundation with JIT user provisioning
51d8496 generated all markdown for the backend
59dc172 finished phase 3 markdown documents
bd4d056 fixed markdown
```

Every commit touching `app/`/`database/` is either infrastructure (Clerk auth/webhook/sync), the Phase-1 catalog+parties+wallet-ledger slice, or automated review fixups (CodeRabbit/SonarQube). There is no commit implementing a game engine, realtime channel, AI integration, marketplace purchase flow, or admin panel. Development activity shifted entirely to writing the 61-file documentation corpus after the Phase-1 code slice landed — the docs describe a future state, not a historical record.
