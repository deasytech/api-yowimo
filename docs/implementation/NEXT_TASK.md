# Current Task

Hardening pass: expand test coverage, tune rate limits on write-heavy endpoints, run a first security review, stand up basic backup/DR.

# Why This Task

Per `docs/implementation/IMPLEMENTATION_ORDER.md` Sprint 14, this is the last sprint in the 14-sprint plan, now that Sprint 13 (AI Host v0) has landed. It deliberately introduces no new product features — only confidence in, and tuning of, what already exists (rate limits on existing endpoints may change; no route, payload, or business logic does).

# Objectives

- [ ] Expand test coverage toward the now-larger surface (Sprints 9–13 each shipped tests, but the plan calls for a dedicated coverage pass — confirm with the user which modules/edge cases to prioritize, don't invent a coverage target).
- [ ] Tune rate limits for the new write-heavy endpoints added since Sprint 1 (purchases, party join/leave, friend requests, push-token registration) — confirm target limits with the user; the current `api` limiter (`60/min` by user or IP) and `webhooks` limiter (`120/min` by IP) were set before most of these endpoints existed.
- [ ] Run a first security review against `docs/architecture/06` (auth) and `docs/architecture/52` (security) — likely best done via the `/security-review` skill against the current `dev` branch; confirm scope with the user before starting.
- [ ] Stand up backup/DR basics proportionate to actual current infra — confirm with the user what "basic" means here (e.g. documented DB backup cadence/restore procedure) versus the full enterprise DR plan in docs 33/58, which is explicitly out of scope.

# Dependencies

Must already exist before starting (all confirmed present):

- The full Sprint 1–13 surface (Auth, Catalog, Party, Wallet, Marketplace, Domain Events, Game Engine, Realtime, Notifications, Friends, Admin, Analytics, AI Host) — this sprint hardens what exists, it doesn't add new domains.

# Files Likely to Change

Impossible to scope precisely without the objective-by-objective decisions above (per `CLAUDE.md`, don't invent scope), but plausible candidates:

- `app/Providers/AppServiceProvider.php` (rate limiter definitions) if limits are retuned.
- New or expanded `tests/Feature/*` files across existing modules.
- Non-code: backup/DR documentation, a security review write-up.

Explicitly not expected to change:

- Any existing API contract (routes, request/response shapes, status codes) — this sprint is about confidence and limits, not behavior.
- Any domain event, listener, or service's business logic.

# Definition of Done

- Whatever coverage/rate-limit/security/DR scope is confirmed with the user is delivered.
- `vendor/bin/pint --dirty --format agent` is clean.
- Full test suite passes (`php artisan test --compact`), including all existing Sprint 1–13 tests unchanged.

# Testing Requirements

- New tests per whatever coverage gaps are confirmed with the user.
- Full regression: `php artisan test --compact` must remain green.

# If Ambiguous

`IMPLEMENTATION_ORDER.md`'s Sprint 14 entry is intentionally broad ("expand test coverage," "tune rate limits," "run a first security review," "stand up backup/DR basics") without specifying targets. Confirm with the user which of the four sub-objectives to tackle first and what "done" looks like for each, per `CLAUDE.md` — do not invent coverage targets, rate-limit numbers, or a DR policy.
