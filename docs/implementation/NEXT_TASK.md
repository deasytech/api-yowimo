# Current Task

Notifications v0: device/push-token registration, FCM integration, and `Notification` classes hooked to existing domain-event listeners.

# Why This Task

Per `docs/implementation/IMPLEMENTATION_ORDER.md` Sprint 9, this is the next item now that Sprint 8 (Realtime/Reverb) has landed. This sprint is a pure consumer of infrastructure that already exists and is already tested: the Sprint 5 events backbone, the Sprint 5 Horizon queue, and (optionally, for in-app notifications) the Sprint 8 broadcasting channels. Its failure mode is contained — "a notification doesn't send" — not a game-state or money bug, which is why the plan rates it low-medium risk.

# Objectives

- [ ] Add a device/push-token registration table (e.g. `push_tokens`: `user_id`, `token`, `platform`, timestamps) + a small service/endpoint for the mobile client to register/unregister a token.
- [ ] Integrate FCM (Firebase Cloud Messaging) as the push channel — Laravel's `Notification` system supports a custom FCM channel; no first-party Laravel FCM channel ships in core, so a small custom `Illuminate\Notifications\Notification`-compatible channel class is expected.
- [ ] Add `Notification` classes for at least the events named in the plan: `PartyMemberJoined`, `RoundCompleted`, `WalletCredited` — each notification is queued (`ShouldQueue`), consistent with `RecordAnalyticsEvent`'s precedent from Sprint 5.
- [ ] Wire these notifications via `app/Listeners` (new listeners that call `$user->notify(...)` in response to the existing domain events), not by modifying the events themselves or the services that dispatch them.
- [ ] Do not change any existing event's payload, dispatch point, or the services that fire them (`WalletService`, `PartyMembershipService`, `GameSessionService`, etc.) — this sprint only adds new listeners downstream of events that already fire correctly.
- [ ] Do not touch `routes/channels.php`, the two Sprint 8 broadcast channels, or any `ShouldBroadcast` event — Notifications (push/FCM) and Realtime (in-app WebSocket) are separate concerns per `docs/architecture/`; don't conflate them without asking.

# Dependencies

Must already exist before starting (all confirmed present):

- `app/Events`/`app/Listeners` — Sprint 5 backbone. `PartyMemberJoined`, `PartyStarted`, `RoundCompleted`, `GameCompleted`, `TurnStarted`, `WalletCredited`, `WalletDebited`, `PurchaseCompleted` all fire fire-after-commit.
- Horizon/queue — active since Sprint 5, proven with `RecordAnalyticsEvent`.
- `App\Models\User` — the notifiable target; already uses Laravel's `Notifiable` trait (confirmed in `docs/audit/MODULE_STATUS.md`).

# Files Likely to Change

New:

- `database/migrations/*_create_push_tokens_table.php`, `app/Models/PushToken.php`.
- `app/Services/Notifications/PushTokenService.php` (or similar) + a controller/route for register/unregister, following the existing Form Request + Resource + Policy pattern used elsewhere in `app/Http`.
- `app/Notifications/*` — one class per notification (e.g. `PartyMemberJoinedNotification`, `RoundCompletedNotification`, `WalletCreditedNotification`), each `ShouldQueue`.
- A custom FCM notification channel (e.g. `app/Notifications/Channels/FcmChannel.php`) plus whatever FCM SDK/HTTP client wrapper it needs — confirm the FCM credential/config approach with the user before choosing a package (see "If Ambiguous").
- `app/Listeners/*` — new listeners bridging existing events to `$user->notify(...)`.
- Tests covering: token registration/unregistration, notification queuing (`Notification::fake()`), and at least one real-queue integration test in the `RecordAnalyticsEventTest.php` style (`config(['queue.default' => 'database'])` + `queue:work --once`).

Edited:

- `routes/api.php` — add the push-token register/unregister route(s), inside the existing `auth:clerk` + `throttle:api` group.

Explicitly not expected to change:

- Any existing event class (`PartyMemberJoined`, `RoundCompleted`, etc.) or the services that dispatch them.
- `routes/channels.php`, `config/broadcasting.php`, `config/reverb.php` — Sprint 8's realtime layer, unrelated to push notifications.
- `WalletService`, `GameSessionService`, `PartyMembershipService` internals.

# Definition of Done

- A user can register a push token via the API and it's persisted, scoped to that user.
- At least `PartyMemberJoined`, `RoundCompleted`, and `WalletCredited` trigger a queued notification to the relevant user(s).
- One notification is proven against the real queue (not `Notification::fake()` alone), mirroring `RecordAnalyticsEventTest.php`'s "actually runs ... through a real queue worker" test.
- `vendor/bin/pint --dirty --format agent` is clean.
- Full test suite passes (`php artisan test --compact`), including all existing Sprint 1–8 tests unchanged.

# Testing Requirements

- New tests for: push-token register/unregister (including replacing an existing token for the same device, if that's the chosen dedup rule — confirm with the user), each new notification firing off its trigger event (`Notification::fake()`), and one real-queue integration test.
- Full regression: `php artisan test --compact` must remain green.

# If Ambiguous

`IMPLEMENTATION_ORDER.md`'s Sprint 9 entry doesn't specify: the FCM integration approach (a specific package like `kreait/laravel-firebase` vs. a hand-rolled HTTP client against FCM's HTTP v1 API), the full list of events that should notify (only three are named as examples — "e.g."), whether in-app (Reverb-broadcast) notifications are in scope alongside push, or the push-token dedup/replacement rule (one token per user, or per device). Confirm these with the user before inventing any of them, per `CLAUDE.md`.
