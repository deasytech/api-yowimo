# Current Task

None confirmed. Since the game engine completion pass, two more unscheduled passes have landed by user decision (see `docs/implementation/CURRENT_PHASE.md`'s Current Sprint for detail):

- **Catalog/default-pack/logging pass** (2026-09-27): a "Tempt" games catalog extracted and synced into packs (`clerk`-style sync command + seeder), `game_types.default_pack_id` so packless parties can resolve a free starter pack and start, single-kind (truth-only/dare-only) packs now playable, and a log-noise fix (expected 4xx exceptions no longer reported as errors; terminal vs. retryable AI-provider failures distinguished so a bad OpenAI key doesn't retry-and-log 4x).
- **Documentation-gap resolution pass** (2026-09-28): a full audit-doc review surfaced staleness plus four items from the list below; the user asked for all of them. Shipped: the friend-request 404→422 bug, Paystack saved-card purge on account deletion, an admin blocked/deleted-accounts view, party ready-check (informational only), card reporting (log-only), and a global XP leaderboard. Full detail in `docs/implementation/CURRENT_PHASE.md`.

# Candidates (need a decision, not a guess)

- **`notification_preferences`** — named in `38_DATABASE_SCHEMA_REFERENCE.md` with no column spec; needs the channel/category list decided.
- **Reward Engine remainder** — daily streaks, combo multipliers, sponsor rewards; no reset/timezone rules or formulas defined anywhere. (Leaderboards shipped 2026-09-28 — see above.)
- **Small follow-ups (newly surfaced 2026-09-28):** `PaymentMethodService::delete()` (the manual single-card-delete endpoint) still doesn't call Paystack's deactivate-authorization endpoint the way account deletion now does — same fix, different call site, not yet applied since it wasn't part of the identified gap; card-report reasons are a fixed 4-value enum (inappropriate/offensive/spam/other) chosen without a spec to confirm against — revisit if product wants different categories; ready-check state doesn't reset when a new game starts (deliberately not built — see CURRENT_PHASE.md).
- **Infra:** GitHub billing lock blocks CI; Sentry DSN unset; production needs a scheduler and a deploy pipeline; Firebase/OpenAI keys per deployed environment.
- **Doc-set internal inconsistencies** (`docs/audit/TECHNICAL_DEBT.md` #11) — Laravel 12 vs 13, Postgres vs MySQL, coverage targets, RTO figures, controller line limits, React Navigation vs Expo Router, across `docs/architecture/00`–`60`. Still unreconciled; not touched in the 2026-09-28 pass since it's a large, separate cleanup, not a code gap.
- **Tier 4 (`IMPLEMENTATION_ORDER.md` §G)** — Chat, Voice/Video, Moderation, Creator Economy, Corporate/Enterprise, i18n — deferred pending a business trigger.

# If Ambiguous

Ask the user which candidate to build next, and confirm its rules up front, as each prior pass did.
