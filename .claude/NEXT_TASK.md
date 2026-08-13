# Current Task

Pack purchase & inventory: let an authenticated user buy a `Pack` with tokens and unlock its full (non-preview) card content.

# Why This Task

Per `docs/implementation/IMPLEMENTATION_ORDER.md` Sprint 3, this is the next item now that Sprint 2 (token purchase / wallet top-up) has landed. It's the second half of the marketplace commerce loop: Sprint 2 got tokens *into* the wallet, this sprint spends them. It also closes the last piece of `docs/audit/TECHNICAL_DEBT.md` #2/#3-adjacent debt: `PackCard` full content currently has no ownership gate at all.

# Objectives

- [ ] Add a new table (name TBD — `user_pack_purchases` or `pack_purchases`, either works) recording `user_id`, `pack_id`, `wallet_transaction_id` (or similar reference back to the debit), timestamps. **This is the first task in the current roadmap that requires a new migration — confirm the table name and columns with the user before writing it**, per `.claude/ARCHITECTURE_RULES.md`'s precedence note and the project's "never modify/add migrations unless requested" rule; this task's own roadmap entry authorizes a new migration, but the exact shape hasn't been confirmed.
- [ ] Add `POST /api/v1/packs/{id}/purchase`: validates the pack is active, checks the user doesn't already own it (return a clear error, not a double-debit), debits via the existing `WalletService::debit()` (catches `InsufficientWalletBalanceException` → surfaced as a normal error response, not a 500), then records ownership.
- [ ] Gate full (non-preview) `PackCard` content behind ownership: `PackPolicy` gains an ability (e.g. `viewFullContent`) and `PackResource`/`PackCardResource` only include non-preview cards for a pack the requesting user owns.
- [ ] Reuse the `Purchase/` service group from Sprint 2 where it fits (e.g. a `PackPurchaseService` alongside the existing `PurchaseService`, or extend `PurchaseService` — decide based on whether token-bundle and pack purchases share enough logic; they use `credit()` vs `debit()` respectively, so they may warrant separate classes).
- [ ] Do not modify `WalletService`, the `Wallet`/`WalletTransaction` models, or any wallet migration — reuse `debit()` exactly as it exists.
- [ ] Do not modify the existing `GET /packs`, `GET /packs/featured`, `GET /packs/{id}` behavior for users who don't own the pack being viewed (preview cards must keep working as today).

# Dependencies

Must already exist before starting (all confirmed present):

- `app/Services/Wallet/WalletService.php::debit()` — idempotent, race-safe, unmodified. This will be the first real user-facing flow to exercise its `InsufficientWalletBalanceException` path — test that explicitly.
- `app/Services/Purchase/PurchaseService.php`, `PaymentProvider`, `ManualPaymentProvider` — Sprint 2, done (token bundle top-up only; pack purchase spends tokens, it doesn't call the payment provider).
- `app/Services/PackService.php::find()` — existing active-pack lookup (404 for missing/inactive), reuse for the purchase endpoint's pack lookup.
- `app/Policies/PackPolicy.php`, `app/Http/Resources/Api/V1/PackResource.php`, `PackCardResource.php` — existing, to be extended.

# Files Likely to Change

New:
- A migration for the new ownership table (name/columns to confirm — see Objectives).
- A model for that table.
- `app/Services/Purchase/PackPurchaseService.php` (or extend `PurchaseService` — decide during implementation).
- `app/Http/Controllers/Api/V1/PackPurchaseController.php`.
- `tests/Feature/Api/V1/PackPurchaseControllerTest.php`.

Edited:
- `routes/api.php` — add `POST /packs/{id}/purchase`.
- `app/Policies/PackPolicy.php` — add an ownership-gated ability.
- `app/Http/Resources/Api/V1/PackResource.php` / `PackCardResource.php` — gate full card content behind ownership.

Explicitly not expected to change:
- `app/Services/Wallet/WalletService.php`
- `app/Models/Wallet.php`, `app/Models/WalletTransaction.php`
- Any existing migration

# Definition of Done

- An authenticated user can `POST /api/v1/packs/{id}/purchase` with an `Idempotency-Key` header, tokens are debited from their wallet, and full pack content becomes visible to them afterward.
- Purchasing with insufficient balance returns a clean error (not a 500), and does not create a partial/ownership record.
- Purchasing a pack the user already owns returns a clear error, not a second debit.
- A user who has not purchased a pack still only sees preview cards (existing behavior unchanged).
- `vendor/bin/pint --dirty --format agent` is clean.
- Full test suite passes (`php artisan test --compact`), including unmodified `tests/Feature/Services/WalletServiceTest.php`.

# Also outstanding from Sprint 1 (lower priority, can be done alongside or after)

- Schedule `clerk:sync-users` as an hourly self-heal job.
- Add a GitHub Actions workflow running Pint + Pest on every PR.

# If Ambiguous

Confirm the new table's name/columns and the service-class split (new `PackPurchaseService` vs. extending `PurchaseService`) with the user before writing code — don't guess, per `CLAUDE.md`.
