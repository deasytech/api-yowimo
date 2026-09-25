# Yowimo API

The backend for **Yowimo**, a mobile party game (Truth or Dare-style) built on Laravel. It powers real-time multiplayer game sessions, a social layer (friends, likes), a token/wallet economy with real payments, an XP and badges reward system, and push/in-app notifications — all served as a JSON API to a separate mobile client.

## Tech stack

- **Laravel 13** / PHP 8.3+
- **Clerk** — identity provider; the API never issues its own auth tokens, it verifies Clerk-issued JWTs (`clerk` guard) and syncs users via Clerk webhooks
- **Filament 5** — admin panel at `/admin`, for managing packs, token bundles, users, parties, and content
- **Laravel Reverb** — WebSocket broadcasting for real-time game/party state
- **Laravel Horizon** — queue dashboard/monitoring (Redis-backed queues)
- **Firebase Cloud Messaging** (`kreait/laravel-firebase`) — push notifications, paired with in-app notification records
- **Paystack** — real payment processing for token bundle purchases (NGN by default, USD for confirmed non-Nigerian buyers), with saved payment methods and webhook reconciliation
- **OpenAI** — optional AI "host" (Yowi) that posts contextual messages during a game; inert until `OPENAI_API_KEY` is configured
- **Sentry** — error tracking; inert until `SENTRY_LARAVEL_DSN` is configured
- **Pest** — test suite (370+ tests)

## Core domain

- **Packs & cards** — Truth/Dare content packs, purchasable with tokens, with preview cards for non-owners
- **Parties & game sessions** — create/join/like parties, start a live game session, take turns, advance rounds
- **Voting & XP** — fellow party members vote on completed turns; XP is awarded automatically and via votes
- **Badges** — awarded automatically based on gameplay milestones
- **Wallet & token bundles** — a token-based currency with a ledger (`wallet_transactions`) as the source of truth; token bundles are purchasable via Paystack or a manual/test provider
- **Friends** — send/accept/reject/cancel friend requests, remove friendships
- **Notifications** — FCM push + in-app, for events like party invites, game completion, wallet activity, and friend requests
- **Admin panel** — Filament-based CMS for managing the above

## Getting started

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
npm install
npm run build
```

Or run the bundled setup script (installs deps, copies `.env`, generates the app key, migrates, and builds assets):

```bash
composer run setup
```

### Required configuration

At minimum, set these in `.env` before the API will authenticate real requests:

- `CLERK_ISSUER`, `CLERK_JWKS_URL` — verify Clerk-issued JWTs on incoming API requests
- `CLERK_WEBHOOK_SECRET` — verifies the signature of incoming Clerk (Svix) webhooks
- `CLERK_SECRET_KEY` — Backend API secret key, used only for server-to-server Clerk API calls (e.g. `clerk:sync-users`) — never exposed to a client
- `DB_*` — a MySQL database (defaults to a local `yowimo` database)
- `REDIS_*` — required for queues (Horizon) and cache

Optional, feature-gated integrations (each fails gracefully / stays inert when unset):

- `PAYSTACK_SECRET_KEY`, `PAYSTACK_PUBLIC_KEY` — real payment processing; without these, purchases fall back to a manual provider (useful for local/test environments)
- `FIREBASE_CREDENTIALS` — push notifications via FCM
- `REVERB_*` — real-time broadcasting
- `OPENAI_API_KEY` — the AI host feature
- `SENTRY_LARAVEL_DSN` — error tracking

### Running locally

```bash
composer run dev
```

This starts the app server, queue worker, log viewer (Pail), Vite, and Reverb together.

## Testing

```bash
composer test
# or
php artisan test
```

The suite uses [Pest](https://pestphp.com) against an in-memory SQLite database (per `phpunit.xml`), Clerk auth faked via a signed-JWT test helper, and Paystack/Firebase calls faked via `Http::fake()`.

Code style is enforced with [Laravel Pint](https://laravel.com/docs/pint):

```bash
./vendor/bin/pint
```

## API documentation

A human-readable API reference is served as a static page at `resources/docs/api-reference.html`.

## Admin panel

The Filament admin panel is available at `/admin` once a super-admin user exists (seeded via `SUPER_ADMIN_PASSWORD` in `.env`).
