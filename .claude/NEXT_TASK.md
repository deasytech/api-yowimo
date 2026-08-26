# Current Task

Analytics & observability baseline: persist an `analytics_events` feed off the existing domain-event backbone, add `/health` checks for DB/Redis/Queue/Broadcast, and wire error tracking (Sentry or equivalent).

# Why This Task

Per `docs/implementation/IMPLEMENTATION_ORDER.md` Sprint 12, this is the next item now that Sprint 11 (Admin panel v0) has landed. It's read-side/observational work with no changes to any write path, which is why the plan rates it low risk.

# Objectives

- [ ] Create an `analytics_events` table (see `docs/architecture/38_DATABASE_SCHEMA_REFERENCE.md`'s `analytics_events` section for the documented column list — `id`, `user_id`, `event`, `payload`, `ip`, `device`, `country`, `created_at`; the doc also lists `tenant_id`, but there is no multi-tenant concept anywhere in this codebase yet, so confirm with the user whether to include that column now or omit it — see "If Ambiguous") + an `AnalyticsEvent` model.
- [ ] Change `App\Listeners\RecordAnalyticsEvent` (`app/Listeners/RecordAnalyticsEvent.php`) to persist a row instead of (or in addition to — confirm with the user) just logging via `Log::info()`. It currently only listens to `PartyCreated`; decide with the user whether this sprint also wires it to the other five events already dispatched by the Sprint 5 backbone (`PartyMemberJoined`, `PartyStarted`, `WalletCredited`, `WalletDebited`, `PurchaseCompleted`) or stays scoped to what already fires.
- [ ] Add health check endpoint(s) covering DB, Redis, Queue, and Broadcast (Reverb) connectivity — Laravel's built-in health check (`php artisan health` / the `health` route added by recent Laravel versions) may already cover some of this; check `routes/` and `config/` before adding a new implementation.
- [ ] Wire error tracking (Sentry or equivalent) — no APM/error-tracking package is installed today; confirm which provider with the user before adding a new external dependency and credentials.

# Dependencies

Must already exist before starting (all confirmed present):

- `app/Events`/`app/Listeners` domain-event backbone (Sprint 5) — this sprint is a consumer of it, not a rework.
- `App\Listeners\RecordAnalyticsEvent` — already queued (`ShouldQueue`), already proven against a real queue worker.

# Files Likely to Change

New:

- `database/migrations/*_create_analytics_events_table.php`, `app/Models/AnalyticsEvent.php`.
- A health-check route/controller (or config for Laravel's built-in one) under `routes/` or `config/`.
- Sentry (or equivalent) config + `.env.example` entries, if a provider is confirmed.

Edited:

- `app/Listeners/RecordAnalyticsEvent.php` — persist instead of/alongside logging.
- Possibly `app/Providers/EventServiceProvider.php` (or wherever listeners are registered) if additional events get wired to analytics recording.

Explicitly not expected to change:

- Any existing API controller, service, or the Filament admin panel added in Sprint 11 — this sprint is observability infrastructure, not a new admin surface (though a future sprint may add an Analytics resource to the panel — not this one, unless asked).
- Any existing event's dispatch call or payload shape.

# Definition of Done

- `analytics_events` rows are persisted for at least the events this sprint scopes in.
- Health check endpoint(s) report DB/Redis/Queue/Broadcast status.
- Error tracking is wired and confirmed to capture a test exception, if a provider was confirmed with the user.
- `vendor/bin/pint --dirty --format agent` is clean.
- Full test suite passes (`php artisan test --compact`), including all existing Sprint 1–11 tests unchanged.

# Testing Requirements

- New tests asserting `RecordAnalyticsEvent` (and any newly wired listeners) persist the expected row.
- New tests for the health check endpoint(s), covering both healthy and simulated-unhealthy states where feasible.
- Full regression: `php artisan test --compact` must remain green.

# If Ambiguous

`IMPLEMENTATION_ORDER.md`'s Sprint 12 entry doesn't specify: whether to include the doc's `tenant_id` column on `analytics_events` given no multi-tenant concept exists yet, which events beyond `PartyCreated` should start recording analytics this sprint, which error-tracking provider to wire (Sentry is named as an example, not a requirement), and what health-check response shape/auth model to use (public vs. admin-gated, given the Sprint 11 admin panel now exists). Confirm these with the user before inventing any of them, per `CLAUDE.md`.
