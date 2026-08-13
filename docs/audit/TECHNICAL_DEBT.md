# Technical Debt — Yowimo Backend

**Audit date:** 2026-07-13

Concrete, evidence-based debt items found in the actual codebase (not doc-vs-code gaps — see `ARCHITECTURE_GAP_ANALYSIS.md` for those). Ordered roughly by impact. This is an analysis-only document; nothing here has been changed.

---

## 1. `WalletService` is fully built but completely unreachable — RESOLVED (Sprint 1)

**Status:** Resolved. `GET /api/v1/wallet` and `GET /api/v1/wallet/transactions` (`WalletController`, `WalletPolicy`, `WalletResource`, `WalletTransactionResource`) now expose `WalletService` read-only, and `UserResource` reports real `balance`/`currency` instead of the old hardcoded `enabled: false` stub. `WalletService` and the wallet models/migrations were not modified. No top-up/purchase (write) path exists yet — that remains Sprint 2 scope, tracked separately (see item 2 below).

## 2. Token bundles have no purchase path — RESOLVED (Sprint 2)

**Status:** Resolved for top-up. `POST /api/v1/token-bundles/{id}/purchase` (`TokenBundlePurchaseController`, `PurchaseService`) credits `WalletService::credit()` with `WalletTransactionType::TopUp`, keyed by a client-supplied, server-enforced `Idempotency-Key` header. Payment collection itself is stubbed via a `PaymentProvider` interface + `ManualPaymentProvider` driver that always approves — swapping in a real gateway (Stripe/Paystack) is future work, tracked as its own later sprint, not blocking this one. Pack purchase/inventory/ownership (Sprint 3) is still open.

## 3. Party lifecycle stops at creation

`PartyController` supports `index`/`store`/`show` only. There is no join, leave, kick, start, or end action, and no `party_members` (or equivalent) table exists — `players_count` on `parties` is a bare integer column with nothing incrementing/decrementing it. A party can be created and discovered but never actually played. This isn't itself "debt" (it's an intentional scope boundary for Phase 1), but the `players_count` column existing unused, and no design note anywhere in code explaining the intended future join model, means the next person to implement joining has no schema hints to build from beyond the counter column's name.

## 4. Stale/misleading inline comment in `UserResource`

Covered in item 1, but worth calling out as a pattern: at least one comment in the codebase describes a *past* state ("wallet phase isn't implemented") as if it were current. If this pattern repeats elsewhere as more phases land, comments will accumulate as a second, competing (and wrong) source of truth alongside the `docs/architecture/` corpus. Worth a deliberate check whenever a "not yet built" comment is encountered near code that touches a domain now known to be built (wallet).

## 5. `HorizonServiceProvider` gate is `local`-only with no non-local path

```php
Gate::define('viewHorizon', fn () => app()->environment('local'));
```

Horizon is installed and configured, but nobody — not even an admin — can view the dashboard outside local development, and no jobs are ever dispatched to any queue yet, so this is currently inert. It becomes a real gap the moment any job is introduced (e.g., a future notification or marketplace job) with no queue-visibility story for staging/production.

## 6. Testing-standard documents disagree with each other on coverage targets

`docs/architecture/19_TESTING_AND_QUALITY_ASSURANCE.md` specifies 80% minimum / 95% for critical services (Wallet, Auth, Game Engine, Payments). `docs/architecture/56_TESTING_STRATEGY.md` specifies 90% backend / 95% services / **100%** for critical financial logic / 80% mobile. These are two different numbers for the same concept, written in different authoring batches, and neither has been reconciled. Whichever a future contributor reads first becomes their bar. Not blocking today (current suite is 80/80 passing and covers everything that exists), but will cause disagreement the moment someone tries to gate a PR on "meets the documented coverage bar."

## 7. No repository layer despite the docs mandating one

`00_READ_ME_FIRST.md`, `21_CODING_STANDARDS_AND_BEST_PRACTICES.md`, `22_BACKEND_SERVICE_CATALOG.md`, and `53_BACKEND_IMPLEMENTATION_GUIDE.md` all specify a Controller→Service→Repository→Model pattern. The real code goes straight from Service to Eloquent Model. This isn't debt in the sense of broken code — the current pattern works and is arguably simpler and more idiomatic for a codebase this size — but it is a standing disagreement between the documented "mandatory" architecture and actual practice. Left unresolved, it invites two possible failure modes: (a) a future contributor or AI assistant reads the docs literally and starts introducing repositories only for the wallet/party modules, producing an inconsistent codebase with two competing patterns, or (b) the docs continue to be ignored, in which case they should be corrected to describe what's actually being built.

## 8. No CI/CD pipeline

`docs/architecture/18_INFRASTRUCTURE_AND_DEVOPS.md` and `51_DEPLOYMENT_PLAYBOOK.md` specify a full GitHub Actions pipeline (Pint/PHPStan/tests/Docker/blue-green deploy). No `.github/workflows` directory exists, so there is currently no automated enforcement that `vendor/bin/pint` or `php artisan test` pass before merge — both are presumably run manually today (per `AGENTS.md`'s pint/pest rules), which is a manual-process risk as soon as more than one contributor is active.

## 9. `clerk:sync-users` backfill command is never scheduled

`app/Console/Commands/SyncClerkUsers.php` exists and is tested, but is not referenced anywhere in `routes/console.php`. It's a manual reconciliation tool today. If Clerk webhooks ever silently fail (network blip, signature mismatch during a key rotation, etc.), there's no automatic periodic backfill to self-heal local user records — someone has to remember to run it by hand.

## 10. `.env.example` is far narrower than what the docs assume operators will configure

`49_ENVIRONMENT_VARIABLE_REFERENCE.md` documents ~90 environment variables across Reverb, LiveKit, AI providers, payment providers, FCM, Sentry, and feature flags. The real `.env.example` has ~50 variables, all standard Laravel + Clerk + AWS/S3 + mail/queue/cache/session, with zero of the realtime/AI/payment/push keys. Not a defect — those systems don't exist yet — but it means the env-var doc cannot currently be used as an onboarding checklist without first filtering out ~40 keys that have no corresponding code, which is easy to get wrong for a new contributor copying values in blind.

## 11. Doc-set internal inconsistencies (see also `ARCHITECTURE_GAP_ANALYSIS.md` §9)

Not codebase debt directly, but functions as documentation debt that will eventually produce codebase debt if followed literally: conflicting Laravel version (12 vs 13), conflicting primary database (PostgreSQL vs MySQL — the real app uses MySQL), conflicting RTO figures (30 min vs 1 hour), conflicting controller line-length limits (50 vs 150), conflicting mobile navigation library (React Navigation vs Expo Router), and a broken cross-reference in doc 43 to a nonexistent `06_AUTHENTICATION_AND_AUTHORIZATION.md` (the real file is `06_SECURITY_STANDARDS.md`). Recommend picking one authoritative value per fact and either fixing the losing docs or explicitly marking them superseded, before more code gets built against whichever version a contributor happens to read.

## 12. Empty placeholder doc files already in the repo

`docs/audit/{01_CURRENT_STATE,02_ARCHITECTURE_GAP_ANALYSIS,03_TECHNICAL_DEBT,04_REFACTOR_QUEUE,05_MODULE_STATUS}.md`, `docs/reports/*.md`, `docs/adr/*.md`, and `docs/implementation/*.md` are all pre-existing, 0-byte files from earlier scaffolding, sitting alongside the four files this audit adds under `docs/audit/` (which use different, unnumbered filenames per this task's instructions). Worth a deliberate decision on whether to populate, rename, or remove the old placeholders so `docs/audit/` doesn't end up with two overlapping-but-differently-named sets of the same four documents.

---

## Not debt (explicitly verified as sound, to avoid re-litigating)

- Wallet ledger immutability (DB schema + Eloquent model hooks both enforce it) — correct, no notes.
- Room-code generation collision handling (retry-on-unique-violation, checks soft-deleted rows) — correct.
- Clerk JIT provisioning race handling (catches unique-constraint violations, re-fetches) — correct.
- Webhook idempotency (checked by `event_id` before processing, tolerates duplicate-delivery races) — correct.
- API exception mapping is centralized in one place (`ApiExceptionRegistrar`) rather than scattered per-controller — good, no notes.
