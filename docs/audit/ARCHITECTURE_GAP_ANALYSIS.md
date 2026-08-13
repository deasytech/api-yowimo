# Architecture Gap Analysis — Vision vs. Reality

**Audit date:** 2026-07-13
**Scope:** All 61 files in `docs/architecture/` (`00_READ_ME_FIRST.md` → `60_PLATFORM_ROADMAP.md`) diffed against the actual codebase on `dev`@`bd4d056`.

## How the documentation set was built

The 61 architecture documents are self-described as three successive authoring batches, not an evolving record of what was actually shipped:

- Docs **00–20** end with doc 20 declaring "🎉 Architecture Handbook Complete (Phase 1)" and proposing docs 21–37 as a follow-on.
- Docs **21–37** end with doc 37 declaring "End of Phase 2 Engineering Handbook."
- Docs **38–60** (schema/API/event/job references, implementation guides, ops manuals) form an unlabeled third batch; doc 60 declares "End of Core Documentation Suite" and proposes a v2.0 roadmap of further docs.

Git history confirms this: the commits that generated the docs (`51d8496 generated all markdown for the backend`, `59dc172 finished phase 3 markdown documents`, `bd4d056 fixed markdown`) come **after** the last feature commit (`53d6c1d feat: Phase 1 catalog/parties API and wallet ledger foundation`). The documentation describes a target architecture written in one continuous effort; it is not a record of engineering decisions made as code was built. Treat it as a spec to build toward, not as ground truth about current state.

## Headline finding

`docs/architecture/60_PLATFORM_ROADMAP.md` marks **"Phase 1: Foundation" as `Status: Completed`**, listing Authentication, Profiles, Friends, Party System, Wallet, Marketplace, Notifications, Realtime, Voice, and Infrastructure as delivered. This is false against the actual code:

| Claimed complete (doc 60) | Actual state |
|---|---|
| Authentication | ✅ True — Clerk JWT auth is real and tested |
| Profiles | 🟡 Partially true — view/edit own profile only |
| Friends | ❌ False — zero code |
| Party System | 🟡 Partially true — create/discover only, no lifecycle |
| Wallet | 🔵 Misleading — the engine exists but has no API surface |
| Marketplace | ❌ False — zero purchase/inventory code |
| Notifications | ❌ False — zero code |
| Realtime | ❌ False — no Reverb, no channels, `BROADCAST_CONNECTION=log` |
| Voice | ❌ False — no LiveKit integration |
| Infrastructure | ❌ False — no CI/CD, no Docker/IaC found |

Anyone (human or AI assistant) trusting doc 60 at face value would believe far more has been built than actually has, and would either skip re-verifying "done" work or build on top of APIs that don't exist.

## Gap by theme

### 1. Game Engine — the platform's core loop doesn't exist

`08_GAME_ENGINE.md` marks itself **CRITICAL** and specifies rounds, turns, 45s timers, AFK detection, voting, reward computation, and multiple card types/modes. None of this has a migration, model, or service. `parties` can be created and read but never started, played, or ended. This is the single largest gap relative to the product's stated identity ("social multiplayer entertainment platform").

### 2. Wallet — built backwards (engine before API)

Unusually, this is a gap in the *opposite* direction from every other module: `WalletService` (`app/Services/Wallet/WalletService.php`) is a genuinely sophisticated, race-safe, idempotent, append-only ledger implementation that matches or exceeds the rigor `06_SECURITY_STANDARDS.md` and `12_WALLET_AND_TOKEN_SYSTEM.md` demand ("balances NEVER edited directly," ledger-derived balance — this exact principle is repeated verbatim across at least 8 docs: 03, 04, 06, 12, 22, 38, 41, 52). But it is unreachable — no controller, no route — and `UserResource` still ships a hardcoded `wallet.enabled: false` stub with a comment claiming the wallet phase isn't implemented. The backend logic is ahead of the API surface, which is the reverse of every other module in this repo. See `TECHNICAL_DEBT.md` item 1.

### 3. Marketplace — catalog without commerce

`13_MARKETPLACE_ARCHITECTURE.md` specifies a full browse→validate→transact→ledger→grant→notify purchase flow. The actual code has `token_bundles` and `packs` as read-only catalogs with no purchase endpoint, no inventory/ownership table, and no code path that ever calls `WalletService::debit()`. The wallet ledger that would power this already exists (see above) but nothing calls into it.

### 4. Realtime, AI, Voice, Notifications — fully speculative

`09_REALTIME_ARCHITECTURE.md`, `11_AI_HOST_ARCHITECTURE.md`, `14_NOTIFICATION_SYSTEM.md`, and the LiveKit references throughout assume Laravel Reverb, an AI provider abstraction, FCM/APNs/SES, and LiveKit are wired in. None of the corresponding packages are in `composer.json`, no config files exist (`config/broadcasting.php`, `config/reverb.php` are both absent), and `.env.example` has none of the ~40 related keys the docs specify in `49_ENVIRONMENT_VARIABLE_REFERENCE.md` (no `REVERB_*`, `LIVEKIT_*`, `OPENAI_*`/`ANTHROPIC_*`/`GEMINI_*`, `FCM_*`). These modules are 100% documentation with zero code.

### 5. Enterprise scope (Corporate/Multi-Tenant/Creator Economy) — scope far beyond current product

Docs 28–30 specify a full multi-tenant SaaS layer (organizations, workspaces, departments, SSO, white-labeling, `tenant_id` on every table) and doc 29 specifies a creator marketplace with 70/30 revenue splits and payout infrastructure. None of this has a single migration column, let alone a table. Given the current app is a single-tenant consumer product with ~13 API routes, this represents the largest vision/reality distance of any theme in the doc set — these docs describe a different, much later-stage company.

### 6. Architecture-pattern mismatch

`00_READ_ME_FIRST.md`, `21_CODING_STANDARDS_AND_BEST_PRACTICES.md`, `22_BACKEND_SERVICE_CATALOG.md`, and `53_BACKEND_IMPLEMENTATION_GUIDE.md` all prescribe a Controller→Service→**Repository**→Model layered architecture with DTOs, Events/Listeners, Jobs, Observers, and Repositories as first-class citizens (`02_SYSTEM_ARCHITECTURE.md` even proposes an `app/Modules/{Domain}/...` structure). The actual code uses **Controller→Service→Model** directly — no repository layer, no DTOs, no domain events, no jobs, no observers exist anywhere in `app/`. This isn't a partial implementation of the documented pattern; it's a different, simpler pattern that happens to work fine for the current scope. If the docs' pattern is adopted going forward, all 13 existing services would need retrofitting, or the docs should be revised to match the simpler pattern actually in use — the project's own `AGENTS.md` instructs following existing code conventions, which currently means *not* using repositories.

### 7. Event-driven architecture is entirely undelivered

`07_EVENT_CATALOG.md` and `41_DOMAIN_EVENT_CATALOG.md` specify dozens of domain events (`PartyCreated`, `WalletCredited`, `RoundCompleted`, etc.) with listener chains and a `noun.verb` broadcast-event naming convention repeated across at least 5 other docs. There is no `Events` or `Listeners` directory in `app/` at all. Every side effect in the current code (e.g., incrementing `likes_count`, updating `last_seen_at`) happens inline inside services, not via dispatched events. This is a coherent, working choice for the current small scope, but it means none of the "events become reusable across analytics/notifications/achievements" value the docs promise is actually available yet.

### 8. Testing/coverage targets vs. actual coverage

Docs `19_TESTING_AND_QUALITY_ASSURANCE.md` and `56_TESTING_STRATEGY.md` specify 80–95% coverage targets (and disagree with each other on the exact numbers — see `TECHNICAL_DEBT.md` item 6) across a testing pyramid that includes E2E, load, chaos, and mobile test layers. The actual test suite (80 Pest tests, 308 assertions) is well-written and covers 100% of what currently exists, but there is no E2E, load, chaos, or mobile test tooling in the repo at all, because there's no realtime/mobile/infra surface yet to test.

### 9. Internal inconsistencies within the doc set itself

Because the docs were authored in three separate batches, they disagree with each other on basic facts. These matter because whichever number a future contributor (or AI assistant) reads first will be treated as ground truth:

| Topic | Doc 00 / 04 | Doc 53 / 56 |
|---|---|---|
| Laravel version | 12 | 13 |
| PHP version | 8.4+ | 8.3+ |
| Primary database | PostgreSQL 17+ | MySQL |
| Coverage target (critical logic) | 95% (doc 19) | 100% (doc 56) |
| RTO | 30 min (doc 33) | 1 hour (doc 58) |
| Controller max length | 50 lines (doc 21) | 150 lines, prefer <80 (doc 53) |
| Mobile navigation | React Navigation (doc 23) | Expo Router (doc 54) |

The actual codebase settles several of these by evidence: it runs **Laravel 13 / PHP 8.3 / MySQL**, matching doc 53/56, not doc 00/04. Doc 43 also references a file, `06_AUTHENTICATION_AND_AUTHORIZATION.md`, that doesn't exist under that name (the real doc 06 is `06_SECURITY_STANDARDS.md`) — a broken internal cross-reference.

## What the gap is not

This is not a story of stalled or abandoned work. The code that exists (Clerk auth, catalog APIs, party creation, the wallet ledger) is well-tested, handles concurrency/race conditions explicitly, and follows a consistent, sensible pattern. The gap is that the documentation describes a mature, multi-year platform roadmap as if it were the current state, while the code is an honest, disciplined "Phase 1" slice. The risk is entirely one of **trust in the docs as a source of truth** — anyone (especially an AI coding assistant instructed to "read the relevant domain document" before implementing, per `AGENTS.md`) needs to independently verify current state before assuming a doc-described component already exists.
