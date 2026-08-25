# Current Task

Friends / social graph v0: friend requests (send/accept/reject/cancel), a friends list, and unfriending.

# Why This Task

Per `docs/implementation/IMPLEMENTATION_ORDER.md` Sprint 10, this is the next item now that Sprint 9 (Notifications) has landed. It's independent of the game loop and of Sprint 9 — no shared infrastructure is required, though the domain events backbone (Sprint 5) and Notifications (Sprint 9) are natural (optional) extension points once the core CRUD exists. Its failure mode is contained to the social graph — not money or game state — which is why the plan rates it low risk.

# Objectives

- [ ] Add a `friendships` table modeling a directed request with status (`pending`/`accepted`/`rejected` — see `docs/architecture/38_DATABASE_SCHEMA_REFERENCE.md`'s `sender_id`/`receiver_id`/`status`/`accepted_at` shape). `blocked` is out of scope for v0: it implies additional visibility/interaction rules (e.g. hiding profiles, suppressing future requests from that user) that no endpoint, DoD criterion, or test in this task defines — treat it as a separate, explicitly-scoped follow-up if the user wants it.
- [ ] `FriendshipService` (or similar) + endpoints for: send a request, accept, reject, cancel a pending outgoing request, unfriend (remove an accepted friendship), and list friends/pending requests — following the existing Form Request + Resource + Policy pattern.
- [ ] Guard against duplicate/overlapping requests (e.g. a pending request already exists in either direction, or the users are already friends) and self-requests.
- [ ] Do not change any existing event, service, or model outside what's needed to add the `User` relation(s) for friendships (mirroring how `wallet()`/`pushToken()` were added to `User` in prior sprints).

# Dependencies

Must already exist before starting (all confirmed present):

- `App\Models\User` — both sides of a friendship.
- `App\Support\ApiResponse`, existing Form Request + Resource + Policy conventions (see any of `PartyMembershipController`/`PartyMembershipService` for the closest precedent: a request/accept-shaped lifecycle over a pivot-like table).

# Files Likely to Change

New:

- `database/migrations/*_create_friendships_table.php`, `app/Models/Friendship.php`.
- `app/Services/Friends/FriendshipService.php` + `app/Http/Controllers/Api/V1/FriendshipController.php`.
- `app/Http/Requests/Api/V1/*` for sending a request (and any other validated input).
- `app/Http/Resources/Api/V1/FriendshipResource.php` (and/or a lightweight `FriendResource` for the accepted-friends list).
- `app/Policies/FriendshipPolicy.php`.
- Tests covering: send/accept/reject/cancel/unfriend, duplicate-request guards, self-request rejection, and listing.

Edited:

- `routes/api.php` — new routes inside the existing `auth:clerk` + `throttle:api` group.
- `app/Models/User.php` — a relation or two for friendships (additive only, mirroring the existing `wallet()`/`pushToken()` pattern).

Explicitly not expected to change:

- Anything in `app/Events`, `app/Listeners`, `app/Notifications` from prior sprints, or the services that dispatch existing events.
- `routes/channels.php`, Realtime (Sprint 8), or push notifications (Sprint 9) — a `FriendRequestReceived`/`FriendRequestAccepted` notification is a reasonable *future* extension of this module but is not required for v0 unless the user asks for it now.

# Definition of Done

- A user can send a friend request, the recipient can accept or reject it, either side can unfriend an accepted friendship, and the sender can cancel a still-pending request.
- Duplicate/self/already-friends requests are rejected with a clear error, not silently accepted or duplicated.
- `vendor/bin/pint --dirty --format agent` is clean.
- Full test suite passes (`php artisan test --compact`), including all existing Sprint 1–9 tests unchanged.

# Testing Requirements

- New tests for every transition in the objectives above, including the guard/error paths.
- Full regression: `php artisan test --compact` must remain green.

# If Ambiguous

`IMPLEMENTATION_ORDER.md`'s Sprint 10 entry doesn't specify: whether cancelling a pending request and rejecting an incoming one are the same operation or distinct, whether unfriending should be a hard delete or a soft "removed" status (for audit/undo purposes), and whether this sprint should also emit domain events (e.g. `FriendRequestAccepted`) for future Notifications/Realtime consumers or is purely REST-only for now. Confirm these with the user before inventing any of them, per `CLAUDE.md`.
