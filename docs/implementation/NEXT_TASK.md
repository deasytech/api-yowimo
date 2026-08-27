# Current Task

None confirmed. Sprint 14 (Hardening pass) was the last sprint in the 14-sprint plan in `docs/implementation/IMPLEMENTATION_ORDER.md`; there is no Sprint 15 defined.

# Why There's No Task Here

`IMPLEMENTATION_ORDER.md:205` is explicit: "If/when any of these [deferred items] gets prioritized, treat it as its own multi-sprint plan appended after Sprint 14, re-running the same dependency analysis against the codebase's state at that time — rather than assuming this document's Tier 1–3 assumptions still hold." Picking one of the candidates below and building it without that confirmation would be inventing scope, which `CLAUDE.md` prohibits.

# Candidates (need a decision, not a guess)

Unscheduled items carried over from `docs/implementation/CURRENT_PHASE.md`'s "Outstanding, unscheduled" list, roughly in order of how self-contained they are:

- **Reward granting on round/game completion** — amount, trigger, recipients all undecided; explicitly descoped from Sprint 7.
- **Broadcasting Wallet/Purchase events and `PartyMemberLeft`** — needs a new per-user private channel plus (for leave) a domain event that doesn't exist yet.
- **Notifications beyond v0** — the remaining 6 of 9 fired events, in-app (Reverb) delivery, and a real Firebase project per environment.
- **Consuming `FriendRequestSent`/`FriendRequestAccepted`** — events exist (Sprint 10), nothing listens yet; smallest of the candidates.
- **In-panel admin password management** — Sprint 11 set passwords via `tinker`/seeder only.
- **Filament Analytics resource/dashboard** — plus populating `analytics_events`' `ip`/`device`/`country` columns.
- **AI Host beyond v0** — full "Yowi" persona (voice, moderation, translation, recommendations), `RoundCompleted` trigger, retry/backoff.
- **Lower priority, not blocking:** schedule `clerk:sync-users` hourly; add a GitHub Actions Pint+Pest workflow (carried over from Sprint 1).
- **Tier 4 (`IMPLEMENTATION_ORDER.md` §G)** — Chat, Voice/Video, Moderation, Creator Economy, Corporate/Enterprise, i18n — explicitly deferred pending a business trigger (a signed customer, measured demand); do not schedule speculatively.

# If Ambiguous

Ask the user which candidate to build next, and get its objectives/acceptance criteria confirmed the same way each prior sprint's scope was confirmed — do not default to the top of this list without asking.
