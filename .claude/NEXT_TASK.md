# Current Task

Party membership & lifecycle: let users join/leave a party, and let the host start/end it.

# Why This Task

Per `docs/implementation/IMPLEMENTATION_ORDER.md` Sprint 4, this is the next item now that Sprint 3 (pack purchase & inventory) has landed. It closes the biggest gap called out in `docs/audit/TECHNICAL_DEBT.md` #3: a party can be created and discovered but never actually played, and `parties.players_count` is a dangling column nothing increments. It's also the last prerequisite before the Game Engine (Sprint 6–7) can start, since rounds/turns need a real set of party members to act on.

# Objectives

- [ ] Add a `party_members` table + model recording who's in a party (columns TBD — likely `party_id`, `user_id`, a role/host flag or rely on `parties.host_id` for that, `joined_at`/`timestamps`, unique per party/user). **Confirm the table name and columns with the user before writing the migration**, per `.claude/ARCHITECTURE_RULES.md`'s precedence note and this project's "never add migrations unless requested" rule — same as Sprint 3's `pack_purchases` table.
- [ ] `POST /api/v1/parties/{id}/join` — adds the authenticated user as a member (respecting `visibility`/`status` rules already enforced by `PartyPolicy::view`; a party that's full (`max_players`) or not joinable in its current `status` should be rejected cleanly, not silently).
- [ ] `DELETE /api/v1/parties/{id}/leave` — removes the authenticated user as a member. Decide what happens if the host leaves (transfer host? block leaving? end the party?) — confirm with the user, don't invent this rule.
- [ ] `POST /api/v1/parties/{id}/start` — host-only, transitions `status` (check `App\Enums\PartyStatus` for the exact transition, e.g. Draft/Scheduled → Live).
- [ ] `POST /api/v1/parties/{id}/end` — host-only, transitions `status` to ended/completed.
- [ ] Wire `parties.players_count` to real membership counts (increment/decrement on join/leave, matching the existing `likes_count` pattern in `PartyLikeService`).
- [ ] Add a `PartyPolicy` ability (or reuse/extend existing ones) for join/leave/start/end — host-only checks for start/end must go through the Policy, not an inline `if` in the controller, per `.claude/ARCHITECTURE_RULES.md` §4.
- [ ] Do not modify `WalletService`, `PurchaseService`, `PackPurchaseService`, or any wallet/pack purchase code — unrelated to this task.
- [ ] Do not modify `PartyLikeService`/`PartyLikeController` — likes are a separate, already-complete feature.

# Dependencies

Must already exist before starting (all confirmed present):

- `app/Services/Parties/PartyService.php`, `app/Models/Party.php`, `App\Enums\PartyStatus`, `App\Enums\PartyVisibility` — existing create/discover/show code to extend, not replace.
- `app/Policies/PartyPolicy.php` — existing `view`/`create`/`like`/`unlike` abilities to extend.
- `app/Services/Parties/PartyLikeService.php` — closest existing precedent for a join-table service with a floor/ceiling-guarded counter (mirrors the `likes_count` increment/decrement pattern, extend to `max_players`-ceiling-guarded for joins).

# Files Likely to Change

New:
- A migration for `party_members` (name/columns to confirm — see Objectives).
- `app/Models/PartyMember.php` + factory.
- `app/Services/Parties/PartyMembershipService.php` (join/leave/start/end logic — mirrors `PartyLikeService`'s shape).
- `app/Http/Controllers/Api/V1/PartyMembershipController.php` (or split further — decide based on how `PartyLikeController` handles like/unlike as one controller).
- `tests/Feature/Api/V1/PartyMembershipControllerTest.php`.

Edited:
- `routes/api.php` — add the four new routes.
- `app/Policies/PartyPolicy.php` — add join/leave/start/end abilities.
- `app/Http/Resources/Api/V1/PartyResource.php` — likely needs a `joined_by_me`/membership-count-related field, mirroring `liked_by_me`.

Explicitly not expected to change:
- `app/Services/Wallet/WalletService.php`, `app/Services/Purchase/*`
- `app/Services/Parties/PartyLikeService.php`, `app/Http/Controllers/Api/V1/PartyLikeController.php`
- Any existing migration

# Definition of Done

- An authenticated user can join a joinable, public (or otherwise visible-to-them) party and appears in its membership; `players_count` increments.
- A full party (`players_count >= max_players`) rejects further joins cleanly.
- A member can leave; `players_count` decrements, never below zero.
- Only the host can start/end their own party; a non-host attempt is rejected (403) via a Policy, not an inline check.
- `vendor/bin/pint --dirty --format agent` is clean.
- Full test suite passes (`php artisan test --compact`), including all existing Party/PartyLike tests unchanged.

# Also outstanding from Sprint 1 (lower priority, can be done alongside or after)

- Schedule `clerk:sync-users` as an hourly self-heal job.
- Add a GitHub Actions workflow running Pint + Pest on every PR.

# If Ambiguous

Confirm the new table's name/columns, the host-leaves-the-party behavior, and the controller split with the user before writing code — don't guess, per `CLAUDE.md`.
