# Current Task

None confirmed. The referral program has shipped (backend only — see `docs/implementation/REFERRAL_PROGRAM.md`): every user gets an auto-generated `referral_code`; a new user claims one manually during onboarding (`POST /referrals/claim`); both the referrer and the referred user are paid a configurable flat reward (default 20 tokens) the moment the referred user completes their first game, reusing the same "first completed game" gate as the `first_party` badge. `GET /referrals/summary`. Deliberately simple — no tiers, no leaderboard rank, no dependency on deep-linking (codes are entered manually). The Friends-screen "Ranking" tab (sorting by XP, replacing the global leaderboard concept) and the referral-entry UI are separate-repo mobile work, not started from here.

Before that: the rewarded-ad token quest shipped (backend only — see `docs/implementation/REWARDED_AD_TOKEN_EARNING.md`): watch a Google AdMob rewarded ad, earn a configurable amount (default 1 token), capped at a configurable daily limit (default 15), credited only on a verified AdMob Server-Side Verification callback through the existing wallet ledger (`WalletTransactionType::Reward`). `POST /ad-rewards/sessions`, `GET /ad-rewards/progress`, `GET /webhooks/admob-ssv`. The mobile SDK integration and `HeroCard.tsx` wiring remain — separate repo, not started from here.

Before that: the game engine completion pass (see `docs/implementation/CURRENT_PHASE.md`'s Current Sprint) shipped: player/host turn complete & skip, final voting window, early end, pause/resume, host-set turn timer, reactions, late joiners, departed-player skipping, results and party-game lookup endpoints, plus the party roster (`GET /parties/{party}/players`), `errors.game_session_id` on the start-game 409, and realtime connection docs (auth endpoint, Echo setup).

# Candidates (need a decision, not a guess)

- **Party ready-check** (`POST /parties/{id}/ready`) — in `08_GAME_ENGINE.md`'s flow ("Ready Check") and `39_REST_API_REFERENCE.md`, but no rules defined (is ready required to start? does it reset between games?). The players list (`GET /parties/{id}/players`) has shipped.
- **Card reporting + minimal moderation queue** (`POST /cards/report`, an admin review screen) — app stores expect a reporting path; needs a decision on reasons and what a report does.
- **`notification_preferences`** — named in `38_DATABASE_SCHEMA_REFERENCE.md` with no column spec; needs the channel/category list decided.
- **Reward Engine remainder** — daily streaks, combo multipliers, sponsor rewards; rewarded-ad earning and referrals are done (see above), these still have no reset/timezone rules or formulas defined anywhere. The global leaderboard concept (`GET /leaderboards`, still live) is being superseded product-side by a friends-only XP ranking tab — confirm before investing further design effort in global leaderboard formulas/reset rules.
- **Small follow-ups:** `POST /friend-requests` returns 404 (not 422) for a soft-deleted receiver; saved Paystack cards are kept after account deletion; no admin view of blocks/deleted accounts.
- **Infra:** GitHub billing lock blocks CI; Sentry DSN unset; production needs a scheduler and a deploy pipeline; Firebase/OpenAI keys per deployed environment.
- **Tier 4 (`IMPLEMENTATION_ORDER.md` §G)** — Chat, Voice/Video, Moderation, Creator Economy, Corporate/Enterprise, i18n — deferred pending a business trigger.

# If Ambiguous

Ask the user which candidate to build next, and confirm its rules up front, as each prior pass did.
