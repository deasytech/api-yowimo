# Rewarded-Ad Token Earning

**Implemented:** 2026-10-03/05.
**Scope:** backend only — the home screen's "+15 Token Quest" button. The React Native app lives in a separate repository and isn't touched here.

---

## Business rules

Watching a Google AdMob rewarded video ad earns tokens, capped per day. All three numbers below are backend config, not hard-coded anywhere (including the mobile app):

- `tokens_per_completed_ad` — default **1**.
- `daily_token_limit` — default **15**, resets on the server's UTC day boundary (not the user's local timezone — an accepted MVP simplification; see `AdRewardService::dailyProgress()`).
- `rewarded_ads_enabled` — a kill switch; `false` rejects both minting a new session (`422`→`503`, see below) and crediting an already-pending one if flipped off mid-flight.

Unused daily allowance does not roll over. The limit is per account, not per device — enforced by `user_id`, never anything device-supplied.

## Configuration

`config/services.php`'s `admob` block, env-backed:

```
ADMOB_REWARDED_ADS_ENABLED=true
ADMOB_REWARDED_ADS_DAILY_LIMIT=15
ADMOB_REWARDED_ADS_TOKENS_PER_AD=1
```

Plus the existing SSV verification settings (`ADMOB_SSV_KEYS_URL`, `ADMOB_SSV_KEYS_CACHE_TTL`) — see below.

## API endpoints

- `POST /ad-rewards/sessions` — mints a single-use, short-lived (15 min) session token for the frontend to attach to the ad request as AdMob's SSV `customData`. `401` unauthenticated, `422` daily cap already reached, `503` if disabled. Rate limit: `ad-rewards`, 20/min/user — generous, since minting credits nothing by itself and legitimate retries (ad-load failures, backgrounding) are normal.
- `GET /ad-rewards/progress` — `{ watched_today, daily_cap, remaining, next_reset_at, enabled, tokens_per_ad, can_earn, server_date }`. `can_earn` (`enabled && remaining > 0`) is the one field the client should actually gate the button on, rather than deriving it from the others itself.
- `GET /webhooks/admob-ssv` — Google's SSV callback. **GET**, not POST like every other webhook here (Paystack, Clerk) — everything arrives in the query string. Server-to-server; the mobile app never sees this call or its response. Its own rate limit (`admob-ssv`, 600/min/IP) rather than the shared `webhooks` bucket (120/min), since many users' callbacks can funnel through a small pool of Google egress IPs and the shared bucket is sized for Paystack/Clerk's much lower volume.

Full request/response shapes and status codes: `resources/docs/api-reference.html` (search "Ad Rewards").

## Reward verification flow (the security boundary)

The mobile app is never trusted to simply assert "I watched it." AdMob's Server-Side Verification moves that trust to Google's own servers:

1. App calls `POST /ad-rewards/sessions` → gets an opaque token.
2. App attaches that token as the ad's `customData` and requests the ad.
3. User watches it to completion.
4. **Google's server** (not the app) calls `GET /webhooks/admob-ssv` directly, with a signed query string.
5. `AdMobSsvVerifier` verifies the signature against Google's published public keys (`GoogleAdMobKeyProvider`, cached ~12h, auto-refreshed once on a verification miss to cover key rotation) before anything else happens.
6. Only on a verified signature does `AdRewardService::verifyAndCredit()` run.

Signature details (confirmed against Google's live SSV docs at implementation time, not assumed from memory): the content signed is the **raw query string, in the exact order Google sent it**, up to (not including) `&signature=` — ECDSA/P-256 over SHA-256, DER-encoded, base64. Critically, `Illuminate\Http\Request::getQueryString()` is unsafe here: Symfony's version silently alphabetizes parameters and re-encodes them, which breaks verification whenever Google's actual order/encoding differs from that normalized form. The raw `QUERY_STRING` server variable is used directly instead.

The app learns whether a reward landed by **polling** `GET /ad-rewards/progress` after watching — there is no synchronous "reward processed" response to the app, since the app is never party to the SSV call at all. (An earlier draft of this feature's spec described a direct client-facing "reward processed" response shape; that doesn't apply to a correctly-SSV'd design — building one would mean the app calling an endpoint to assert its own reward, which is exactly the insecure pattern SSV exists to avoid.)

## Wallet/ledger integration

No parallel economy. A credit flows through the existing `WalletService::credit()` with `WalletTransactionType::Reward` — the same type `GrantGameCompletionReward` already uses for the game-completion reward, each distinguished by its `description` and `reference` (the `ad_reward_sessions` row vs. a game session). No new wallet, balance column, or transaction type was introduced.

## Idempotency & concurrency

Layered, not just an in-memory check:

- `ad_reward_sessions.token_hash` is unique — the session-lookup itself can't resolve to two different rows.
- A session only ever transitions `pending → credited` once (checked under `lockForUpdate()`); an already-resolved or unknown token is a silent no-op.
- `WalletService::credit()` is called with its own `idempotencyKey` (`ad-reward-session-{id}`), which is DB-unique per wallet — a second credit attempt for the same session can never double-apply even if the status check were somehow bypassed.
- `ad_reward_sessions.ad_network_transaction_id` is unique too, as a second layer tied to Google's own event id.

The daily cap is enforced inside the same transaction that locks the user's wallet row (`Wallet::lockForUpdate()`) — a concurrent `verifyAndCredit()` for the same user blocks on that row until the first one commits, so its own recount of today's total is never stale. Two near-simultaneous callbacks at the boundary (14 credited, two requests arrive together) resolve to exactly one credit; the loser sees the now-updated count and rejects itself. Tested directly (`AdRewardServiceTest.php`, `AdRewardWebhookControllerTest.php`) by driving straight to the boundary with fabricated/pre-credited rows rather than attempting genuine OS-level concurrency, which isn't practical to assert on in a synchronous PHP test — the same convention the rest of this codebase's race-condition tests already use.

## Anti-abuse

- Replay (same valid SSV request resent): no-op past the first credit — tested directly.
- Forged/tampered signature: rejected before `verifyAndCredit()` ever runs; logged (`Log::warning`, IP + `key_id` only — never the query string itself, since `customData` is this flow's bearer credential and nothing in an unverified request is trustworthy to log as fact).
- Spoofing another user: there's no user id in the SSV payload for the backend to trust in the first place — the session (and therefore the credited user) is resolved entirely from the opaque token, which was minted for a specific authenticated user and never round-trips through anything the ad network or client controls.
- Daily-cap/expired/disabled rejections are also logged (`Log::warning`) for auditability, matching the project's existing `Log::warning('message', [...])` convention — though note this is *more* logging than the closest existing precedent (`PaystackWebhookController` logs nothing on a bad signature); the added logging here is a deliberate choice given this endpoint is the primary crediting path for a token-reward surface, not just a confirmation/audit log like Paystack's.

## Database

One new table, `ad_reward_sessions` — not a redundant convenience table. It's structurally required for the claim-token mechanism itself (storing the SSV token hash, the pending→credited/expired lifecycle), which has no equivalent in the existing wallet ledger (the ledger only records *completed* transactions, never a token awaiting async verification). The "how many has this user earned today" query runs directly against this table's own `(user_id, status, credited_at)` index — no separate daily-counter table, and this table is never the balance source of truth (the wallet ledger remains that; this table's `credited_at`/`status` are only updated *after* a successful `WalletService::credit()` call, inside the same transaction).

## Testing

29 tests across 4 files (`AdRewardServiceTest`, `AdMobSsvVerifierTest`, `AdRewardSessionControllerTest`, `AdRewardWebhookControllerTest`), including a genuine end-to-end crypto round-trip: a self-generated EC P-256 keypair signs real requests that are verified through the actual HTTP route, not mocked. Covers: happy path, daily-cap boundary (15th credits, 16th/at-cap rejects), replay, expiry, tampered signature, unknown `key_id` triggering a refresh-and-retry, disabled-at-mint and disabled-mid-flight, and configuration overrides (non-default limit/reward amount actually take effect).

## Status

**IMPLEMENTED** (backend): config, migration/model, `AdRewardService`, SSV verification (`AdMobSsvVerifier`/`GoogleAdMobKeyProvider`), both controllers, both resources, rate limiting, exception handling, logging, tests, API docs.

**REQUIRES EXTERNAL CONFIGURATION** (not possible from this repo):
- A real AdMob account/app registration and rewarded ad unit — nothing here needs AdMob credentials backend-side (ad unit/app IDs are frontend/build-time only), but the SSV callback URL (`https://<your-domain>/api/v1/webhooks/admob-ssv`) must be entered by hand in the AdMob console. In local dev, this needs re-entering every time the dev tunnel's URL rotates, or SSV callbacks silently never arrive.
- `GoogleAdMobKeyProvider`'s keys URL and `AdMobSsvVerifier`'s canonicalization algorithm were confirmed against Google's live SSV docs at implementation time (2026-10) — worth a quick re-check against current docs before go-live if much time has passed, since this was flagged as the one piece most likely to have changed.

**REQUIRES MOBILE WORK** (separate repo, not started from here): `react-native-google-mobile-ads` SDK install + native rebuild, `lib/ads/admob.ts`, the `HeroCard.tsx` button's state machine (mint → load ad → show → poll progress → reconcile), new `EXPO_PUBLIC_ADMOB_*` env vars. See the original planning doc for the full frontend task breakdown.

**BLOCKED:** nothing on the backend side — it's fully testable today via Google's public test ad unit IDs and a real AdMob App ID (test units still need one registered app, just not a real production ad unit).
