# Current Task

Domain events & listeners backbone: introduce `app/Events`/`app/Listeners`, retrofit existing services to dispatch events after their transactions commit, and prove the Horizon queue actually processes a job end-to-end.

# Why This Task

Per `docs/implementation/IMPLEMENTATION_ORDER.md` Sprint 5, this is the next item now that Sprint 4 (party membership & lifecycle) has landed. It's flagged in the plan's dependency graph (`IMPLEMENTATION_ORDER.md` §D) as "the single structural insight" of the whole roadmap: Realtime (Sprint 8), Notifications (Sprint 9), Analytics (Sprint 12), and the Game Engine's reward payouts (Sprint 7) all sit behind this piece of infrastructure, so building it now — once, early — is cheaper than bolting a bespoke trigger into each of those features later.

# Objectives

- [ ] Introduce `app/Events` and `app/Listeners` directories following standard Laravel event/listener conventions.
- [ ] Fire events for what already exists, dispatched **after** each owning transaction commits (fire-after-commit, not mid-transaction):
  - `PartyCreated` — from `PartyService::create()`.
  - `PartyMemberJoined` — from `PartyMembershipService::join()`.
  - `PartyStarted` — from `PartyMembershipService::start()`.
  - `WalletCredited` — from `WalletService::credit()`.
  - `WalletDebited` — from `WalletService::debit()`.
  - `PurchaseCompleted` — from `PurchaseService`/`PackPurchaseService` on successful purchase.
- [ ] Put one real listener on the Horizon queue (`ShouldQueue`) — e.g. an analytics-event-recording listener that just logs/persists that an event fired — to prove the queue path works end-to-end (Horizon is installed and configured per `.claude/IMPLEMENTATION_STATUS.md` but no job has ever been dispatched through it yet).
- [ ] Do not change the outward behavior of any retrofitted service — these are additive `event()` calls, not logic changes. Existing tests for `WalletService`, `PartyService`, `PartyMembershipService`, `PurchaseService`, and `PackPurchaseService` must pass unmodified.
- [ ] Do not touch `WalletService`'s ledger arithmetic, locking, or idempotency logic — only add a dispatch call at the point the transaction commits.

# Dependencies

Must already exist before starting (all confirmed present):

- `app/Services/Wallet/WalletService.php`, `app/Services/Purchase/PurchaseService.php`, `app/Services/Purchase/PackPurchaseService.php`, `app/Services/Parties/PartyService.php`, `app/Services/Parties/PartyMembershipService.php` — the services to retrofit.
- Horizon/queue config — installed, configured, but currently idle (see `docs/audit/MODULE_STATUS.md`).

# Files Likely to Change

New:

- `app/Events/PartyCreated.php`, `PartyMemberJoined.php`, `PartyStarted.php`, `WalletCredited.php`, `WalletDebited.php`, `PurchaseCompleted.php`.
- `app/Listeners/*` — at minimum one queued listener proving the Horizon path works.
- `tests/Feature/Events/*` or equivalent — assert each event fires with the right payload, using `Event::fake()`.

Edited:

- `app/Services/Wallet/WalletService.php`, `app/Services/Purchase/PurchaseService.php`, `app/Services/Purchase/PackPurchaseService.php`, `app/Services/Parties/PartyService.php`, `app/Services/Parties/PartyMembershipService.php` — add a dispatch call each, after the owning `DB::transaction()` commits.

Explicitly not expected to change:

- Any existing migration.
- The ledger/locking/idempotency internals of `WalletService`.
- Any existing controller, route, policy, or resource.

# Definition of Done

- All six events fire from the correct service method, after (not during) the owning transaction, verified with `Event::fake()`.
- At least one listener is queued (`ShouldQueue`) and actually runs a job through Horizon in a test (`Queue::fake()` + assert pushed, or an integration test against the queue connection).
- `vendor/bin/pint --dirty --format agent` is clean.
- Full test suite passes (`php artisan test --compact`), including all existing Wallet/Party/Purchase tests unchanged.

# Testing Requirements

- New tests asserting each event dispatches with the expected payload from its owning service call.
- A test proving the first queued listener is pushed onto the queue and processes without error.
- Full regression: `php artisan test --compact` must remain green.

# If Ambiguous

The exact listener(s) beyond the one proving the queue path, and the precise event payload shape (full model vs. IDs only), aren't specified in `docs/implementation/IMPLEMENTATION_ORDER.md` — confirm with the user before inventing either, per `CLAUDE.md`.
