# Current Task

None confirmed. The game engine completion pass (see `docs/implementation/CURRENT_PHASE.md`'s Current Sprint) has shipped: player/host turn complete & skip, final voting window, early end, pause/resume, host-set turn timer, reactions, late joiners, departed-player skipping, results and party-game lookup endpoints, plus the party roster (`GET /parties/{party}/players`), `errors.game_session_id` on the start-game 409, and realtime connection docs (auth endpoint, Echo setup).

# Candidates (need a decision, not a guess)

- **Party ready-check** (`POST /parties/{id}/ready`) — in `08_GAME_ENGINE.md`'s flow ("Ready Check") and `39_REST_API_REFERENCE.md`, but no rules defined (is ready required to start? does it reset between games?). The players list (`GET /parties/{id}/players`) has shipped.
- **Card reporting + minimal moderation queue** (`POST /cards/report`, an admin review screen) — app stores expect a reporting path; needs a decision on reasons and what a report does.
- **`notification_preferences`** — named in `38_DATABASE_SCHEMA_REFERENCE.md` with no column spec; needs the channel/category list decided.
- **Reward Engine remainder** — daily streaks, combo multipliers, sponsor rewards, leaderboards; no reset/timezone rules or formulas defined anywhere.
- **Small follow-ups:** `POST /friend-requests` returns 404 (not 422) for a soft-deleted receiver; saved Paystack cards are kept after account deletion; no admin view of blocks/deleted accounts.
- **Infra:** GitHub billing lock blocks CI; Sentry DSN unset; production needs a scheduler and a deploy pipeline; Firebase/OpenAI keys per deployed environment.
- **Tier 4 (`IMPLEMENTATION_ORDER.md` §G)** — Chat, Voice/Video, Moderation, Creator Economy, Corporate/Enterprise, i18n — deferred pending a business trigger.

# If Ambiguous

Ask the user which candidate to build next, and confirm its rules up front, as each prior pass did.
