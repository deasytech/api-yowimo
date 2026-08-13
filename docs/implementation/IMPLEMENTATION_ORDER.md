# Implementation Order — Yowimo Backend

**Date:** 2026-07-13
**Basis:** `docs/architecture/00`–`60` (the vision) cross-referenced with `docs/audit/CURRENT_STATE.md`, `MODULE_STATUS.md`, `ARCHITECTURE_GAP_ANALYSIS.md`, and `TECHNICAL_DEBT.md` (the reality), all produced from direct code inspection on `dev`@`bd4d056`.

This is a planning document only — no code changes are made here. It proposes the safest order to move the codebase from its current Phase‑1 slice toward the documented architecture, sized as weekly sprints for a small team (the docs' own roadmap assumes a founder + 1–2 engineers at this stage — see `docs/architecture/37_TECHNICAL_ROADMAP_AND_FUTURE_VISION.md`).

---

## Guiding principles

1. **Preserve what's proven.** Auth, the catalog APIs, party create/discover, party likes, and the wallet ledger are correct, tested, and handle concurrency properly. Nothing in this plan rewrites them — it builds around and on top of them.
2. **Expose before you extend.** Where a backend engine already exists but has no API (wallet), ship the thin exposure layer before adding new domain logic elsewhere. It's the cheapest, lowest-risk work available and it unblocks everything commerce-related.
3. **Events before consumers.** Notifications, realtime, analytics, and AI all want to react to "something happened." Build the event-dispatch backbone once, early, rather than bolting a bespoke trigger into each new feature.
4. **REST/poll before realtime.** New stateful domains (party membership, game rounds) should be built and tested as ordinary request/response APIs first. Layer Reverb broadcasting on top of already-correct, already-tested domain logic — don't debug game-state bugs and socket-infrastructure bugs at the same time.
5. **Narrow the doc scope to what the product needs next.** The docs specify a multi-year, multi-tenant, creator-economy platform. This plan only sequences the modules needed to make Yowimo a working, playable, monetizable single-tenant consumer product. Enterprise/creator/i18n/AR-VR scope is explicitly deferred (see final section) — building it now would be speculative given no evidence of demand yet.
6. **One irreversible-risk item at a time.** Anything touching money (wallet-adjacent) or data model foundations (party membership, game session shape) gets its own sprint with no other structural change layered in, so a regression is easy to isolate.

---

## A. Preserve as-is (no changes required)

These are correct, tested, and should not be touched except for the specific, isolated fixes called out in section B.

| Module | Why it's safe to leave alone |
|---|---|
| Clerk authentication (`app/Services/Clerk/*`, `clerk` guard) | Handles JWT verification, JWKS caching, JIT provisioning races, webhook idempotency — all tested, matches doc 06's delegated-auth model exactly. |
| Game/Pack catalog reads (`GameTypeController`, `PackController`, `GameTypeService`, `PackService`) | Read-only, well-tested, filtering/pagination logic is real and correct. |
| Party create/discover/show (`PartyService`, `RoomCodeGenerator`) | Race-safe room-code generation, transactional create, correct visibility rules. |
| Party likes (`PartyLikeService`, `PartyLikeController`) | Idempotent, floor-guarded, tested. |
| Wallet ledger internals (`WalletService`) | Row-locked, idempotent, append-only-enforced at model + DB level. This is the highest-quality code in the repo — extend around it, never inside it. |
| `ApiResponse` / `ApiExceptionRegistrar` | Single consistent response/error envelope already in place; every new controller should reuse it, not invent a new shape. |
| Test infrastructure (`FakesClerk`, `FakesClerkWebhook`, Pest setup) | Reusable, correct — extend with new `Feature` test files following the same pattern rather than introducing a second testing style. |

---

## B. Refactor-only (existing code, small isolated changes)

These need code changes, but to *existing* files/behavior — not new domain modules. Low risk, high leverage; this is where work should start.

| Item | Change needed | Closes |
|---|---|---|
| `UserResource` wallet stub | Replace the hardcoded `wallet.enabled: false` block with real data from `WalletService`/`Wallet` model | `TECHNICAL_DEBT.md` #1, #4 |
| Wallet has no API | Add a thin `WalletController` (`show`, `transactions`) calling the existing service — no changes to `WalletService` itself | `TECHNICAL_DEBT.md` #1 |
| `clerk:sync-users` never scheduled | Add a scheduled entry in `routes/console.php` (e.g. hourly) as a self-healing backstop to webhook delivery | `TECHNICAL_DEBT.md` #9 |
| `HorizonServiceProvider` gate is local-only | Extend the gate to allow authenticated admins outside `local`, once an admin/role concept exists (depends on Sprint 11) | `TECHNICAL_DEBT.md` #5 |
| `parties.players_count` is a dangling counter | Wire it to real increments/decrements once party membership exists (Sprint 4) rather than leaving it an always-`1` column | `TECHNICAL_DEBT.md` #3 |
| No CI enforcement of Pint/Pest | Add a GitHub Actions workflow running `vendor/bin/pint --test` and `php artisan test` on PRs — no application code touched | `TECHNICAL_DEBT.md` #8 |
| Doc-set internal inconsistencies | Non-code: reconcile Laravel/PHP/DB version claims, coverage targets, RTO figures across `docs/architecture/*` so future work has one authoritative number to build against | `TECHNICAL_DEBT.md` #11 |

---

## C. Completely missing modules (net-new)

Grouped by the product tier they unlock. No code exists for any of these — no migration, model, route, or config.

**Tier 1 — completes the core product loop:**
Marketplace purchase flow (token top-up + pack purchase + inventory), Party membership/lifecycle (join/leave/start/end), Game Engine (rounds/turns/timers/scoring/rewards).

**Tier 2 — makes the core loop feel alive and retains users:**
Domain events/listeners backbone, Realtime (Reverb channels), Notifications (push/in-app), Friends/social graph.

**Tier 3 — operational maturity and first AI feature:**
Admin panel (content management + audit visibility), Analytics/observability baseline, AI Host v0 (narrow scope).

**Tier 4 — explicitly deferred (see final section):**
Voice/Video (LiveKit), Moderation/Trust & Safety at scale, Creator Economy, Corporate/Multi-Tenant/Enterprise, Internationalization, Sponsorship as a first-class entity, Chat/messaging.

---

## D. Dependency graph

Read as "row depends on column being done first."

| Module | Depends on |
|---|---|
| Wallet API exposure | Wallet ledger (done) |
| Token purchase (top-up) | Wallet API exposure |
| Pack purchase / inventory | Token purchase (shares the debit/credit machinery + idempotency pattern) |
| Party membership (join/leave) | Party create/discover (done) |
| Party lifecycle (start/end) | Party membership |
| Domain events backbone | Nothing new — introduced once, retrofitted onto existing services (wallet, party) as low-risk additions |
| Game Engine (rounds/turns) | Party lifecycle (need a roster to deal turns to) + Pack/PackCard catalog (done, supplies content) |
| Game Engine (timers/scoring/rewards) | Game Engine (rounds/turns) + Wallet API (rewards pay out via the real ledger) |
| Realtime (Reverb) | Domain events backbone (broadcast is "one more listener" on events that already fire correctly over REST) |
| Notifications | Domain events backbone + queue actually processing jobs (Horizon is installed but idle today) |
| Friends/social graph | Users (done) — independent of the game loop, can run in parallel if a second engineer is available |
| Admin panel | Whatever data currently needs a write path (Users, Parties, Packs, Wallet transactions as read-only) — independent of the game loop |
| Analytics/observability | Domain events backbone (events are the natural analytics feed) |
| AI Host v0 | Domain events backbone (reacts to `RoundCompleted`/`GameCompleted`) + Realtime (delivers its output into the party channel) |
| Everything in Tier 4 | The full Tier 1–3 stack, plus business signals not yet present (corporate demand, creator supply, etc.) |

The single structural insight this graph produces: **the domain-events backbone is the one piece of infrastructure that unlocks the most downstream work** (Realtime, Notifications, Analytics, AI all sit behind it) even though no document treats it as a headline feature. It should land early, right after the wallet-exposure quick win, specifically because everything after it gets cheaper once it exists.

---

## E. Weekly sprint plan

Each sprint assumes ~1 engineer-week of focused work; adjust pacing to actual team size. Every sprint ends with `vendor/bin/pint --dirty` clean and `php artisan test` green, per existing project convention — this plan doesn't repeat that as a line item per sprint.

### Sprint 1 — Foundation hardening & wallet exposure
*No new domain logic; closes existing debt, unblocks commerce.*
- [x] Wire real `Wallet`/`WalletService` data into `UserResource`; remove the stale stub.
- [x] Add `GET /wallet` and `GET /wallet/transactions` (thin controller over existing service) + a `WalletPolicy`.
- [ ] Schedule `clerk:sync-users` as an hourly self-heal job.
- [ ] Add a GitHub Actions workflow: Pint check + Pest suite on every PR.
- **Risk:** near zero — no changes to `WalletService` internals, no schema changes.

### Sprint 2 — Token purchase (wallet top-up)
- [x] `PurchaseService` (new, `app/Services/Purchase/`) + a `PaymentProvider` interface with a single `ManualPaymentProvider` "manual/test" driver for now (real Stripe/Paystack integration is a later, separate sprint once a provider is chosen).
- [x] `POST /token-bundles/{id}/purchase`: validates bundle is active (404 if inactive/unknown), requires a server-enforced `Idempotency-Key` header, calls `WalletService::credit()`.
- [x] Tests mirror the existing `WalletServiceTest` idempotency patterns (retry-with-same-key does not double-credit).
- **Risk:** low — reuses `WalletService`'s existing idempotent-credit path; only new code is the controller/service wiring and the purchase record itself.

### Sprint 3 — Pack purchase & inventory
- [x] `pack_purchases` table (`pack_id`, `user_id`, `wallet_transaction_id`, unique per pack/user) + model; `POST /packs/{id}/purchase` debits via `WalletService::debit()`, grants ownership. Race-guarded via a wallet-row lock in `PackPurchaseService` so a concurrent double-purchase can't double-charge.
- [x] Full (non-preview) `PackCard` content gated behind ownership — `PackService::find()` loads the full card set only for an owning viewer; `PackResource` exposes `owned_by_me`.
- [x] `debit()`'s `InsufficientWalletBalanceException` path exercised and tested from the real purchase flow, not just the happy path.
- **Risk:** low-medium — first time `debit()` is exercised from a real user-facing flow; the failure path is tested explicitly.

### Sprint 4 — Party membership & lifecycle
- `party_members` table + model; `POST /parties/{id}/join`, `DELETE /parties/{id}/leave`, host-only `POST /parties/{id}/start` and `POST /parties/{id}/end`.
- Wire `parties.players_count` to real membership counts (closes the dangling-column debt item).
- Extend `PartyPolicy` for member-only visibility where relevant.
- **Risk:** medium — first new core table since Phase 1; keep the state machine (`draft → scheduled → live → ended`) minimal and test every transition, including invalid ones (e.g., joining a full or ended party).

### Sprint 5 — Domain events & queue activation
- Introduce `app/Events` and `app/Listeners`. Fire events for what already exists: `PartyCreated`, `PartyMemberJoined`, `PartyStarted`, `WalletCredited`, `WalletDebited`, `PurchaseCompleted` — refactoring existing services to dispatch, not changing their outward behavior.
- Put the first real job on the Horizon queue (e.g., an analytics-event-recording listener) to prove the queue path actually works end-to-end, not just in config.
- **Risk:** medium — touches multiple existing, tested services to add `event()` calls. Mitigate by adding events as a final step inside existing transactions (fire-after-commit, per the pattern the docs themselves recommend) and re-running the full existing suite, which should catch any behavioral regression immediately since it already covers these code paths.

### Sprint 6 — Game Engine: rounds & turns (data + state machine)
- `game_sessions`, `rounds`, `turns` tables; `GameSessionService`.
- `POST /parties/{id}/game/start` (host-only, requires `live` party status from Sprint 4) creates a session, deals cards from the party's `Pack`, advances turns via explicit `POST /game/{id}/next-turn` (poll-driven, no timers yet).
- Scope to the existing `PackCardKind` (Truth/Dare) only — do not attempt the doc's full card-type catalog yet.
- **Risk:** medium-high (largest new module so far) — mitigate by building it entirely REST/poll-driven first, deferring timers and realtime to later sprints so game-logic bugs and infrastructure bugs aren't debugged simultaneously.

### Sprint 7 — Game Engine: timers, scoring, completion
- Server-authoritative turn timer (scheduled tick or queued delayed job — now safe to build since Sprint 5 activated the queue), AFK handling, round/game completion.
- On completion, grant rewards via the existing `WalletService::credit()` path (reuses Sprint 1–3 machinery) and fire `RoundCompleted`/`GameCompleted` events (reuses Sprint 5 backbone).
- **Risk:** medium — timer correctness under server restarts/worker crashes needs explicit test coverage (resume behavior), since this is the first time-sensitive logic in the codebase.

### Sprint 8 — Realtime (Reverb)
- Install `laravel/reverb`, add `config/broadcasting.php`, define presence channel for the party lobby and a private channel for an active game session.
- Broadcast the events already fired since Sprint 5–7 (`PartyMemberJoined`, `TurnStarted`, `RoundCompleted`, etc.) — this sprint should not need to touch game-logic code at all, only add broadcasting listeners.
- **Risk:** medium, but contained — because the domain logic being broadcast was already built and tested REST-first, a realtime bug here is isolated to the transport layer, not the game rules.

### Sprint 9 — Notifications v0
- Device/push-token registration table; FCM integration; `Notification` classes hooked to existing Listeners (e.g., notify on `PartyMemberJoined`, `RoundCompleted`, `WalletCredited`).
- Runs on the queue activated in Sprint 5.
- **Risk:** low-medium — purely additive consumer of already-correct events; failure mode is "notification doesn't send," not "game state is wrong."

### Sprint 10 — Friends / social graph
- `friends`/`friend_requests` tables, request/accept/reject endpoints.
- Independent of the game loop; sequenced here to avoid context-switching the core team before the play loop is solid, but flagged as parallelizable earlier if a second engineer is available.
- **Risk:** low — new, isolated domain with no dependency on money or game state.

### Sprint 11 — Admin panel v0
- Install Filament; resources for Users, Parties, Wallet transactions (read-only/audit), GameTypes/Packs/PackCards/TokenBundles (this becomes the real write path for content, replacing "seed-only" catalogs).
- Extend the `viewHorizon` gate to admin roles now that an admin concept exists.
- **Risk:** low — additive tooling over existing tables; the main risk is scope creep (docs specify a huge admin surface — build only Users/Parties/Content/Wallet-audit now, defer the rest).

### Sprint 12 — Analytics & observability baseline
- Persist an `analytics_events` feed off the Sprint 5 event backbone; add `/health` checks for DB/Redis/Queue/Broadcast; wire error tracking (Sentry or equivalent).
- **Risk:** low — read-side/observational work, no changes to write paths.

### Sprint 13 — AI Host v0 (narrow scope)
- A single `AIProvider` interface with one concrete OpenAI implementation (no multi-provider fallback chain yet — that's premature for a v0).
- One prompt, triggered by `RoundCompleted`/`GameCompleted` (Sprint 5/7 events), delivered as a message into the game's realtime channel (Sprint 8).
- **Risk:** low-medium — scoped narrowly on purpose; the doc's full "Yowi" persona (voice, moderation, translation, recommendations) is out of scope for this sprint.

### Sprint 14 — Hardening pass
- Expand test coverage toward the now-larger surface; tune rate limits for the new write-heavy endpoints (purchases, joins); run a first security review against `docs/architecture/06`/`52`; stand up backup/DR basics proportionate to actual current infra (not the full enterprise DR plan in doc 33/58).
- **Risk:** low — this sprint produces no new user-facing behavior, only confidence in what exists.

---

## F. Recommended safest migration order (condensed)

1. Expose the wallet (Sprint 1) — zero-risk, unblocks commerce.
2. Wire commerce: token purchase → pack purchase (Sprints 2–3) — reuses the proven ledger, adds the first money-moving user flows one at a time.
3. Add party membership/lifecycle (Sprint 4) — the last purely-CRUD-shaped foundation piece before anything stateful.
4. Build the domain-events backbone (Sprint 5) — the single highest-leverage infrastructure investment; do this before, not after, realtime/notifications/analytics/AI, since all four consume it.
5. Build the Game Engine REST-first (Sprints 6–7) — get the rules and state machine correct without also debugging sockets.
6. Layer Realtime on top of already-correct game logic (Sprint 8) — transport-only risk, not logic risk.
7. Add Notifications and Friends (Sprints 9–10) — both are additive consumers of what now exists; order between them is interchangeable.
8. Add Admin and Analytics (Sprints 11–12) — operational tooling, no dependency on anything after Sprint 5 except "there's now enough data to administer/observe."
9. Ship the first, narrow AI feature (Sprint 13) — deliberately last among Tier 1–3 work because it depends on everything before it (events, realtime) and is the least proven/most speculative piece to build against real usage data.
10. Harden (Sprint 14) before considering any Tier 4 scope.

---

## G. Explicitly deferred (not sprinted)

Per `docs/architecture/ARCHITECTURE_GAP_ANALYSIS.md`, these represent a different, later-stage company scope and should not be scheduled until there's a concrete business trigger (a signed corporate customer, a creator waitlist, measured international demand, etc.), because building them speculatively now would be exactly the vision/reality gap this plan exists to close:

- **Voice/Video (LiveKit)** — deferred until the core game loop retains users without it.
- **Chat/messaging** — deferred; likely a prerequisite discussion for Moderation, not a standalone early win.
- **Moderation/Trust & Safety** — deferred until Chat/Friends produce enough user-generated surface to moderate.
- **Sponsorship as a first-class entity** — the `parties.is_sponsored`/`sponsor_name` columns already exist as a hint; build the real entity only once a sponsor pipeline exists commercially.
- **Creator Economy** — deferred; no creator-facing surface exists to build a payout system for yet.
- **Corporate / Multi-Tenant / Enterprise** — deferred; would require retrofitting `tenant_id` across every table above, which is far cheaper to do once (before scale) than repeatedly — but only once there's a real enterprise customer to build against, not speculatively.
- **Internationalization** — deferred until the single-locale product has traction worth localizing.

If/when any of these gets prioritized, treat it as its own multi-sprint plan appended after Sprint 14, re-running the same dependency analysis against the codebase's state at that time rather than assuming this document's Tier 1–3 assumptions still hold.
