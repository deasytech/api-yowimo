# Backup & DR Basics

**Assessed:** 2026-08-27, as part of Sprint 14 (Hardening pass).
**Scope:** deliberately minimal — proportionate to actual current infra, not the full enterprise DR plan in `docs/architecture/33_DISASTER_RECOVERY_AND_BUSINESS_CONTINUITY.md` / `58_DISASTER_RECOVERY_AND_BUSINESS_CONTINUITY.md`, which remain aspirational and out of scope here.

---

## Current state

No production infrastructure is provisioned yet. There is no Docker/IaC, no CI/CD, and no hosted database — `.env` is `APP_ENV=local` against a local MySQL instance. This confirms `docs/audit/ARCHITECTURE_GAP_ANALYSIS.md`'s finding that the "Infrastructure" chapter of the architecture docs describes a target state, not what exists today.

Because nothing is deployed, there is nothing to back up yet. The policy below is what to apply the day a production database is provisioned — not a description of anything currently running.

## Policy to adopt at first production deploy

1. **Automated backups.** Use whatever managed database provider is chosen (e.g. a managed MySQL/Postgres offering from AWS RDS, PlanetScale, Railway, DigitalOcean, etc.) and enable its built-in automated daily backups. Do not hand-roll a backup script unless the chosen provider has no managed backup option.
2. **Point-in-time recovery (PITR).** Enable PITR/binlog-based recovery if the provider offers it, so recovery isn't limited to the last daily snapshot.
3. **Retention.** Keep at minimum 7 daily backups. Extend once real usage/compliance needs are known — not invented here.
4. **Restore drill.** Once a production database exists, run one manual test restore (into a throwaway instance) to confirm the backup is actually restorable, then repeat quarterly. An untested backup is not a backup.
5. **Ownership.** Whoever provisions the production database is responsible for confirming items 1–4 are enabled before real user data is stored in it.

## Explicitly out of scope

- Multi-region failover, RTO/RPO targets, runbooks, and the full incident-response process in docs 33/58 — those assume infrastructure and scale this project doesn't have yet.
- Any specific provider recommendation — no hosting decision has been made, so none is assumed here.
