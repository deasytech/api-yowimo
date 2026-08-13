# Current Task

Expose the Wallet via a read-only API and remove the stale `UserResource` wallet stub.

# Why This Task

Per `.claude/CURRENT_PHASE.md` and `.claude/IMPLEMENTATION_STATUS.md`, this is the only task marked **Critical** priority, and it is the unstarted first item of Sprint 1 in `docs/implementation/IMPLEMENTATION_ORDER.md`. Three factors make it the single highest-priority task available right now:

- **Highest priority:** it's the lead item of the current (not-yet-started) sprint, ahead of every other candidate.
- **Lowest risk:** `app/Services/Wallet/WalletService.php` is already complete, transactional, idempotent, and race-safe, with its own passing test suite (`tests/Feature/Services/WalletServiceTest.php`). This task adds a read path on top of it — it does not touch ledger logic, does not change the schema, and cannot corrupt wallet state.
- **Most foundational:** per the dependency graph in `docs/implementation/IMPLEMENTATION_ORDER.md`, Sprint 2 (token purchase) and Sprint 3 (pack purchase) both require the wallet to be reachable via API first. No other unstarted task in the current sprint unblocks downstream work the way this one does.

It also closes the single most misleading piece of technical debt in the codebase: `app/Http/Resources/Api/V1/UserResource.php` currently hardcodes `'wallet' => ['enabled' => false, 'balance' => 0, 'currency' => 'points']` with a comment claiming the wallet phase isn't implemented — which is no longer true and actively misinforms any client reading it (see `docs/audit/TECHNICAL_DEBT.md` #1).

# Objectives

- [ ] Add `WalletPolicy` so a user may only view their own wallet.
- [ ] Add `WalletController@show` — `GET /api/v1/wallet` — returns the authenticated user's wallet (balance, currency), lazily creating one via `WalletService::walletFor()` if it doesn't exist yet.
- [ ] Add `WalletController@transactions` — `GET /api/v1/wallet/transactions` — cursor-paginated ledger history for the authenticated user's wallet, newest first.
- [ ] Add `WalletResource` and `WalletTransactionResource` for consistent API output.
- [ ] Add `IndexWalletTransactionRequest`, reusing the existing `HasCursorPagination` concern for consistency with other index endpoints.
- [ ] Register both routes in `routes/api.php` inside the existing `auth:clerk` + `throttle:api` group.
- [ ] Replace the hardcoded wallet stub in `UserResource` with real balance/currency data (or remove the field if the mobile client is expected to call `GET /wallet` directly instead — confirm which approach before implementing; default to keeping the field but populated with real data, to avoid an unannounced breaking change for the client).
- [ ] Do not modify `WalletService`, the `Wallet`/`WalletTransaction` models, or any migration.

# Dependencies

Must already exist before starting (all confirmed present):

- `app/Services/Wallet/WalletService.php` — complete, tested, unmodified by this task.
- `app/Models/Wallet.php`, `app/Models/WalletTransaction.php` — complete, unmodified by this task.
- `wallets`, `wallet_transactions` migrations — already applied, unmodified by this task.
- `clerk` auth guard and `auth:clerk` middleware — already wired in `app/Providers/AppServiceProvider.php`.
- `App\Support\ApiResponse` / `App\Support\ApiExceptionRegistrar` — existing response envelope and exception mapping to reuse, not replace.
- `app/Http/Requests/Api/V1/Concerns/HasCursorPagination.php` — existing pagination concern to reuse for the transactions index.

# Files Likely to Change

New:

- `app/Http/Controllers/Api/V1/WalletController.php`
- `app/Policies/WalletPolicy.php`
- `app/Http/Resources/Api/V1/WalletResource.php`
- `app/Http/Resources/Api/V1/WalletTransactionResource.php`
- `app/Http/Requests/Api/V1/IndexWalletTransactionRequest.php`
- `tests/Feature/Api/V1/WalletControllerTest.php`

Edited:

- `routes/api.php` — add `GET /wallet` and `GET /wallet/transactions`
- `app/Http/Resources/Api/V1/UserResource.php` — replace the hardcoded wallet stub with real data
- `tests/Feature/Api/V1/MeControllerTest.php` — update any assertion on the old stub shape

Explicitly not expected to change:

- `app/Services/Wallet/WalletService.php`
- `app/Models/Wallet.php`, `app/Models/WalletTransaction.php`
- `database/migrations/*wallet*`
- `tests/Feature/Services/WalletServiceTest.php`

# Definition of Done

- An authenticated user can `GET /api/v1/wallet` and receive their real balance and currency, with a wallet auto-created on first access.
- An authenticated user can `GET /api/v1/wallet/transactions` and receive their real, cursor-paginated ledger history.
- A user cannot view another user's wallet or transactions (`403`).
- `UserResource` no longer contains the hardcoded `enabled: false` stub; it reflects real wallet data (or the field is removed by deliberate, documented decision — not by oversight).
- `vendor/bin/pint --dirty --format agent` is clean.
- The full test suite passes, including new and updated tests (`php artisan test --compact`).
- No change was made to `WalletService`, the wallet models, or any migration.

# Testing Requirements

- New `tests/Feature/Api/V1/WalletControllerTest.php` covering:
    - Auth required (401 without a valid token) for both endpoints.
    - Wallet is lazily created on first `GET /wallet` for a user with no existing wallet row.
    - Correct balance/currency returned for a user with existing transactions.
    - `GET /wallet/transactions` returns correct ordering (newest first), correct pagination (`per_page`, `cursor`, `has_more_pages`, `next_cursor`), and an empty-history case.
    - A user cannot view another user's wallet or transaction list (policy denial, `403`).
- Update `tests/Feature/Api/V1/MeControllerTest.php` (or wherever `UserResource`'s shape is asserted) to match the new wallet field contents.
- Full regression: `php artisan test --compact` must remain green, including the unmodified `tests/Feature/Services/WalletServiceTest.php`, to confirm the ledger itself was not touched.

# Documentation Updates

After this task lands, update:

- `docs/audit/MODULE_STATUS.md` — change Wallet from "🔵 Built, unexposed" to "✅ Built" (or "🟡 Partial" if only read endpoints ship, with purchase/write still pending).
- `docs/audit/TECHNICAL_DEBT.md` — mark debt item #1 (unreachable `WalletService` / stale `UserResource` stub) as resolved.
- `docs/implementation/IMPLEMENTATION_ORDER.md` — check off the wallet-exposure portion of Sprint 1.
- `docs/implementation/IMPLEMENTATION_PROGRESS.md` — move Wallet from "In progress" to "Completed" (or update its remaining-work note if only partially shipped).
- `.claude/CURRENT_PHASE.md` — update Current Sprint / Current Priority to reflect Sprint 1's remaining items (CI, scheduling `clerk:sync-users`) or advance to Sprint 2 if those are already done.
- `.claude/IMPLEMENTATION_STATUS.md` — update the Wallet API row's Status/Complete %/Priority.
- `.claude/NEXT_TASK.md` — replace this file's content with the next recommended task (Sprint 2: token purchase, per `IMPLEMENTATION_ORDER.md`).
