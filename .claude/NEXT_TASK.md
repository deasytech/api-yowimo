# Current Task

Token purchase (wallet top-up): let an authenticated user buy a `TokenBundle` and have the tokens land in their `Wallet` via the existing ledger.

# Why This Task

Per `docs/implementation/IMPLEMENTATION_ORDER.md` Sprint 2, this is the next item once Sprint 1's wallet exposure landed (`.claude/CURRENT_PHASE.md`). It's the first module that connects the now-reachable wallet (`GET /wallet`, `GET /wallet/transactions`) to a real money-in flow, and it unblocks Sprint 3 (pack purchase), which follows the same pattern.

# Objectives

- [ ] Add a `PurchaseService` with a pluggable payment-provider interface; ship a single "manual/test" driver for now — do not integrate a real payment provider (Stripe/Paystack) as part of this task, that's a separate later sprint once a provider is chosen.
- [ ] Add `POST /api/v1/token-bundles/{id}/purchase`: validates the bundle is active, then calls the existing `WalletService::credit()` with `WalletTransactionType::TopUp`, a `TokenBundle` reference, and a required `Idempotency-Key` header (server-enforced, per `.claude/ARCHITECTURE_RULES.md` §3).
- [ ] Add a `TokenBundlePolicy@purchase` (or reuse `view`) ability check in the controller.
- [ ] Do not modify `WalletService`, the `Wallet`/`WalletTransaction` models, or any existing migration — reuse `credit()` exactly as it exists.
- [ ] Do not modify `TokenBundleController@index` or the existing `GET /token-bundles` route/behavior.

# Dependencies

Must already exist before starting (all confirmed present):

- `app/Services/Wallet/WalletService.php::credit()` — idempotent, race-safe, unmodified.
- `app/Http/Controllers/Api/V1/WalletController.php`, `GET /wallet`, `GET /wallet/transactions` — Sprint 1, done.
- `app/Services/TokenBundleService.php`, `app/Models/TokenBundle.php` — catalog list, unmodified by this task.
- `App\Enums\WalletTransactionType::TopUp` — existing enum case to reuse.

# Files Likely to Change

New:
- `app/Services/PurchaseService.php` (or `app/Services/Marketplace/PurchaseService.php` — confirm placement before creating; this repo has no `Marketplace` module directory yet, don't invent one without checking `.claude/ARCHITECTURE_RULES.md` precedence note first)
- `app/Http/Controllers/Api/V1/TokenBundlePurchaseController.php` (or a `purchase` method on the existing `TokenBundleController` — confirm which fits the existing thin-controller convention before implementing)
- `app/Http/Requests/Api/V1/PurchaseTokenBundleRequest.php`
- `tests/Feature/Api/V1/TokenBundlePurchaseControllerTest.php`

Edited:
- `routes/api.php` — add `POST /token-bundles/{id}/purchase`

Explicitly not expected to change:
- `app/Services/Wallet/WalletService.php`
- `app/Models/Wallet.php`, `app/Models/WalletTransaction.php`
- `database/migrations/*wallet*`

# Definition of Done

- An authenticated user can `POST /api/v1/token-bundles/{id}/purchase` with an `Idempotency-Key` header and receive a credited wallet transaction plus updated balance.
- Retrying the same request with the same `Idempotency-Key` does not double-credit (mirrors `WalletServiceTest`'s existing idempotency coverage).
- Purchasing an inactive or unknown bundle returns a 404/422 (no wallet mutation).
- `vendor/bin/pint --dirty --format agent` is clean.
- Full test suite passes (`php artisan test --compact`), including unmodified `tests/Feature/Services/WalletServiceTest.php`.

# Also outstanding from Sprint 1 (lower priority, can be done alongside or after)

- Schedule `clerk:sync-users` as an hourly self-heal job.
- Add a GitHub Actions workflow running Pint + Pest on every PR.

# If Ambiguous

Confirm placement/naming (service directory, controller-vs-method split) with the user before writing code — don't guess, per `CLAUDE.md`.
