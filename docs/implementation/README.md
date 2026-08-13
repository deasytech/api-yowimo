# Implementation Tracking — README

## Purpose of this folder

`docs/implementation/` tracks the *actionable, near-term* build plan — as opposed to `docs/architecture/`, which is the long-term vision, and `docs/audit/`, which is the point-in-time comparison between the two. This folder answers "what do we build next, in what order, and why," not "what's the final platform" or "how far off are we."

## How implementation works

1. `docs/audit/` establishes ground truth (what's actually built, verified against code).
2. `IMPLEMENTATION_ORDER.md` turns that gap into a sequenced, dependency-aware plan — weekly sprints from the current state toward a complete core product, with explicitly deferred scope (enterprise/creator/i18n) at the end.
3. `CURRENT_PHASE.md`, `IMPLEMENTATION_PROGRESS.md`, and `NEXT_TASK.md` are the living trackers that get updated as sprints actually execute — they answer "where are we right now," not "where should we eventually be" (that's `IMPLEMENTATION_ORDER.md`'s job).

**Note:** as of this writing, the equivalent live-status files under `.claude/` (`CURRENT_PHASE.md`, `IMPLEMENTATION_STATUS.md`) are the ones being kept current; the files of the same purpose in this folder are placeholders pending population. Treat `.claude/` as the source of truth for "status right now" until this folder's trackers are filled in and one location is chosen going forward.

## Workflow

```
docs/audit/*  →  IMPLEMENTATION_ORDER.md  →  execute one sprint  →  update CURRENT_PHASE.md / IMPLEMENTATION_PROGRESS.md / NEXT_TASK.md  →  repeat
```

- Never start a sprint out of order without checking its **Dependencies** row/section — the plan is sequenced specifically to keep risk low (expose-before-extend, events-before-consumers, REST-before-realtime).
- If reality diverges from the plan (a sprint reveals new debt, a dependency was wrong), update `IMPLEMENTATION_ORDER.md` itself rather than silently drifting — it's a living plan, not a fixed spec.
- Re-run the relevant `docs/audit/` document if a change is large enough to shift the overall gap picture, rather than trusting a stale audit.

## How engineers use these documents

- **Starting a new task?** Check `NEXT_TASK.md` (or `.claude/CURRENT_PHASE.md` until this folder is populated) before picking work yourself — it reflects the sequencing decisions already made in `IMPLEMENTATION_ORDER.md`.
- **Not sure if something already exists?** Check `docs/audit/MODULE_STATUS.md` first, not `docs/architecture/` — the architecture docs describe the destination, not current reality.
- **Planning a new module?** Find it in `IMPLEMENTATION_ORDER.md`'s dependency graph before writing code — building out of order re-introduces exactly the risk this plan exists to avoid.
- **Finishing a task?** Update `IMPLEMENTATION_PROGRESS.md` and `NEXT_TASK.md` so the next person (human or AI) doesn't have to re-derive status from git log.
