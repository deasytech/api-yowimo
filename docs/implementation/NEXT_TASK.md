# Current Task

Admin panel v0: install Filament and expose read/audit and content-management resources for Users, Parties, Wallet transactions, and the game/pack catalog.

# Why This Task

Per `docs/implementation/IMPLEMENTATION_ORDER.md` Sprint 11, this is the next item now that Sprint 10 (Friends / social graph) has landed. It's purely additive tooling over existing tables — no dependency on money movement or game state beyond what already exists, which is why the plan rates it low risk. The main risk is scope creep: `docs/architecture/` specifies a much larger admin surface than this sprint should build.

# Objectives

- [ ] Install `filament/filament` and configure a panel (auth-gated to admin users — see "If Ambiguous" for how "admin" is determined, since no role/permission concept exists yet).
- [ ] Filament resources, scoped to what the plan names: Users (view/edit, not full CRUD — deleting a user is a sensitive action out of scope unless asked), Parties (view/audit), Wallet transactions (**read-only** — this is a ledger; never expose create/edit/delete on `WalletTransaction` through the admin UI, only through `WalletService`), GameTypes/Packs/PackCards/TokenBundles (full CRUD — the plan explicitly calls this out as "becomes the real write path for content, replacing seed-only catalogs").
- [ ] Extend the existing `viewHorizon` gate (see wherever it's currently defined, likely `AppServiceProvider`) to admin roles now that an admin concept exists, without changing its current `local`-only behavior for non-admin environments unless asked.
- [ ] Do not build the rest of the documented admin surface (moderation tools, analytics dashboards, enterprise/multi-tenant admin, etc.) — defer everything not explicitly named above.

# Dependencies

Must already exist before starting (all confirmed present):

- `App\Models\User`, `Party`, `WalletTransaction`, `GameType`, `Pack`, `PackCard`, `TokenBundle` — all the resources this sprint administers.
- Clerk auth (`auth:clerk` guard) — Filament's own panel auth is a separate concern from the API's guard; decide how panel login works (see "If Ambiguous").

# Files Likely to Change

New:

- `app/Providers/Filament/*PanelProvider.php` (from `filament:install`).
- `app/Filament/Resources/*` — one resource per module above.
- Tests covering: resource visibility/authorization, wallet-transaction resource is read-only, catalog resources support create/edit/delete.

Edited:

- `composer.json`/`composer.lock` — new dependency.
- `config/` — Filament's published config, plus any Horizon gate file if it needs updating for the admin role.
- `routes/` — Filament registers its own routes via its panel provider; no changes expected to `routes/api.php`.

Explicitly not expected to change:

- Any existing API controller, service, resource, or policy in `app/Http`, `app/Services` — this sprint is a new, separate admin surface, not a rework of the API layer.
- `WalletService` itself — the admin UI reads the ledger, it does not gain a new way to write to it.

# Definition of Done

- An admin user can log into the Filament panel and view/manage the resources named in Objectives; a non-admin user cannot.
- Wallet transactions are visible but not creatable/editable/deletable from the panel.
- `vendor/bin/pint --dirty --format agent` is clean.
- Full test suite passes (`php artisan test --compact`), including all existing Sprint 1–10 tests unchanged.

# Testing Requirements

- New tests for panel access control (admin vs non-admin) and for each resource's allowed operations, especially the wallet read-only guard.
- Full regression: `php artisan test --compact` must remain green.

# If Ambiguous

`IMPLEMENTATION_ORDER.md`'s Sprint 11 entry doesn't specify: how "admin" is determined (a new `is_admin` boolean/role column on `users`, a hardcoded email allowlist, or something else — there is currently no role/permission concept anywhere in the codebase), whether Users should be editable at all from the panel or view-only like Parties, and how Filament's panel login relates to the existing Clerk-only auth (a separate password-based admin login, or bridging Clerk into Filament's guard). Confirm these with the user before inventing any of them, per `CLAUDE.md`.
