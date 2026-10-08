# Referral Program

**Implemented:** 2026-10-07.
**Scope:** backend only — replaces a fully-mock referrals screen. The React Native app lives in a separate repository and isn't touched here.

---

## Business rules

Deliberately simple — no milestone tiers, no leaderboard rank, and no dependency on the (separately tracked, still-broken) universal deep-linking work. A new user enters a referral code **manually** during onboarding; nothing here requires an invite link to actually open the app.

- Every user — new or existing — has a unique `referral_code`, auto-generated server-side (`ReferralCodeGenerator`: 8 uppercase alphanumeric characters, collision-checked before use).
- A user can claim exactly one referral code, ever (`users.referred_by_user_id`, settable once). Claiming is rejected if the code doesn't resolve to a user, the caller already claimed one, or the code is the caller's own.
- `reward_amount` is backend config (`REFERRAL_REWARD_AMOUNT`, default 20), never hard-coded client-side — same pattern as the ad-reward token quest's `tokens_per_completed_ad`.
- Payout trigger: the moment the **referred user** completes their first game (the same "first completed game" gate `EvaluateGameCompletionBadges` uses for the `first_party` badge), both the referrer and the referred user are credited `reward_amount` tokens.

## Configuration

`config/services.php`'s `referrals` block, env-backed:

```
REFERRAL_REWARD_AMOUNT=20
```

## API endpoints

- `POST /referrals/claim` — body `{ code }`. `401` unauthenticated, `422` invalid/self-referral code, `409` already claimed. Rate limit: `referrals`, 10/min/user.
- `GET /referrals/summary` → `{ referral_code, referred_count, tokens_earned, reward_amount }`. `referred_count`/`tokens_earned` are the viewer's own lifetime totals; `reward_amount` is live config, not a stored/cached value.

Full request/response shapes and status codes: `resources/docs/api-reference.html` (search "Referrals").

## Payout flow (anti-abuse)

The mobile app is never trusted to assert "I referred someone" — the payout is driven entirely by server-side gameplay state:

1. New user claims a code during onboarding → `referred_by_user_id` set. No tokens move yet.
2. User plays and completes a game (the existing game engine's own completion flow — nothing referral-specific here).
3. `GameCompleted` fires, same as it always has. `GrantReferralReward` (a new listener, alongside the already-existing `EvaluateGameCompletionBadges`, `GrantGameCompletionReward`, etc.) checks: is this user referred, and is this their first-ever completed game session?
4. If so, `ReferralService::payoutFor()` credits both parties the configured `reward_amount` via the existing `WalletService`, `WalletTransactionType::Referral`.

The "must complete a first party" gate (not just signup) is the main defense against throwaway-account farming — it matches the bar already set for the `first_party` badge, so it's at least consistent. No stronger anti-abuse (device fingerprinting, velocity limits, etc.) was requested or built.

## Idempotency & concurrency

- `claim()` locks the caller's own user row (`lockForUpdate()`) before checking/setting `referred_by_user_id`, serializing a double-tap race the same way `PushTokenService`/`ClerkUserProvisioner` already do for their own row. No formal idempotency key is needed for the endpoint itself: a retry of a claim after it already succeeded just hits `ReferralAlreadyClaimedException` (409) harmlessly.
- The payout itself is idempotent via `WalletService::credit()`'s own per-wallet `idempotency_key` (`referral-reward-{referredUserId}`, the same key used for both the referrer's and the referred user's credit) — a referred user's second, third, etc. completed game still triggers `GrantReferralReward`'s check, but `payoutFor()` is only invoked when `completedGameSessionCount === 1`, and even a duplicate invocation (e.g. a queue retry) can't double-credit because the idempotency key collision just returns the existing transaction.
- `ReferralCodeGenerator` (and `FallbackUsernameGenerator`, which it mirrors) checks uniqueness before insert, not atomically with it — a concurrent collision is caught and retried once by `ClerkUserProvisioner`/`ClerkUserSynchronizer`, the same pattern already established for usernames.

## Database

Two new nullable columns on `users` (no new table): `referral_code` (string, unique) and `referred_by_user_id` (nullable FK to `users`, `nullOnDelete`). A migration backfills every pre-existing user with a generated code (`eachById()`-paginated, safe against the shrinking-WHERE-set bug a prior backfill command hit, since each update removes that row from the next page's `WHERE referral_code IS NULL` the same way). Every new user gets one at creation time, via `ClerkUserProvisioner::createUser()` and `ClerkUserSynchronizer::sync()` — mirroring exactly how those two already backfill a fallback `username` for OAuth-only sign-ins.

## Testing

Across `ReferralServiceTest`, `ReferralControllerTest`, `GrantReferralRewardTest`, plus referral-code-specific additions to the existing `ClerkUserProvisionerTest`/`ClerkUserSynchronizerTest`. Covers: claim happy path (case-insensitive code), invalid code, already-claimed (even with a different, otherwise-valid code), self-referral, live summary values, payout to both parties exactly once, no payout for a never-referred user, no payout once the referrer has been deleted, and the same generation/collision-retry coverage the username fallback already has.

## Status

**IMPLEMENTED** (backend): schema + backfill migration, `ReferralCodeGenerator`, `ReferralService`, both endpoints, `GrantReferralReward` listener, rate limiting, exception handling, tests, API docs.

**REQUIRES MOBILE WORK** (separate repo, not started from here): the onboarding "enter a referral code" field, the referrals screen (currently fully mocked) wired to these two endpoints, and the Friends screen's new "Ranking" tab (sorts friends by `xp`, which `PartyHostResource` now exposes — see the prior XP-on-friends follow-up).
