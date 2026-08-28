# Current Task

None confirmed. In-app notifications — the recommendation this file previously carried — has shipped (see `.claude/CURRENT_PHASE.md`'s Current Sprint section: `notifications` table, `InAppChannel`, all 10 `*Notification` classes updated, `GET /notifications`/`PATCH /notifications/read`/`PATCH /notifications/read-all`). Sprint 14 was the last numbered sprint in the 14-sprint plan in `docs/implementation/IMPLEMENTATION_ORDER.md`; there is no Sprint 15 defined, and nothing below is confirmed as the next pick.

# Why There's No Task Here

`IMPLEMENTATION_ORDER.md:205` is explicit: "If/when any of these [deferred items] gets prioritized, treat it as its own multi-sprint plan appended after Sprint 14, re-running the same dependency analysis against the codebase's state at that time — rather than assuming this document's Tier 1–3 assumptions still hold." Picking one of the candidates below and building it without that confirmation would be inventing scope, which `CLAUDE.md` prohibits.

# Candidates (need a decision, not a guess)

Unscheduled items carried over from `docs/implementation/CURRENT_PHASE.md`'s "Outstanding, unscheduled" list, roughly in order of how self-contained they are. Each was checked directly against the relevant `docs/architecture/` file, not assumed from the earlier July audit:

- **Reward granting on round/game completion** — checked against `08_GAME_ENGINE.md`'s "Reward Engine"/"Scoring Engine" sections: a full reward/scoring/achievement subsystem (voting, MVP, creativity, daily streaks, XP, badges, combo multipliers), not a small add-on. Amount/trigger/recipients are genuinely undecided business scope; explicitly descoped from Sprint 7.
- **A real Firebase project per environment** — blocked on the user providing credentials, not a coding task (same reason it's been unscheduled since Sprint 9).
- **`notification_preferences`** (per-channel opt-in/opt-out) — named in `38_DATABASE_SCHEMA_REFERENCE.md` with no column spec given (a "future" placeholder, like doc 14's "Future: Email/SES" note); undefined enough to need its own scoping pass before it can be built.
- **In-panel admin password management** — checked against `16_ADMIN_PANEL_ARCHITECTURE.md`: appears only as one bullet in a security-requirements checklist ("Strong Password Policy," alongside MFA/session timeout/IP logging), not a specified feature. Sprint 11 set passwords via `tinker`/seeder only; thin doc grounding for anything beyond that.
- **Filament Analytics resource/dashboard** — the dashboard itself is small, but populating `analytics_events`' `ip`/`device`/`country` columns needs request context threaded through every service call site, a wider-blast-radius change.
- **AI Host beyond v0** — the full "Yowi" persona is effectively Tier-4-sized scope (voice, moderation, translation, recommendations); `RoundCompleted` trigger and retry/backoff are smaller, reasonable follow-ups.
- **Lower priority, not blocking:** schedule `clerk:sync-users` hourly; add a GitHub Actions Pint+Pest workflow (carried over from Sprint 1).
- **Tier 4 (`IMPLEMENTATION_ORDER.md` §G)** — Chat, Voice/Video, Moderation, Creator Economy, Corporate/Enterprise, i18n — explicitly deferred pending a business trigger (a signed customer, measured demand); do not schedule speculatively.

# If Ambiguous

Ask the user which candidate to build next, and get its objectives/acceptance criteria confirmed the same way each prior sprint's scope was confirmed — do not default to the top of this list without asking.
