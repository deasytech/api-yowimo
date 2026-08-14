# Implementation Status — Yowimo Backend

**Assessed:** 2026-07-13, `dev`@`bd4d056`, by direct code inspection against `docs/architecture/`. Analysis only — no code modified.
**Sources:** `docs/audit/*`, `docs/implementation/IMPLEMENTATION_ORDER.md`, `.claude/CURRENT_PHASE.md`.

**Status legend:** ✅ Complete · 🟡 Partial · 🔵 Built, unexposed · ⬜ Missing
**Priority legend:** Critical (do next) · High · Medium · Low · Deferred (no sprint scheduled — see `IMPLEMENTATION_ORDER.md` §G)

| Module | Status | Complete % | Needs Refactor? | Priority | Dependencies |
|---|---|---|---|---|---|
| Authentication (Clerk) | ✅ Complete | 100% | No | Maintain | — |
| Game/Pack/PackCard Catalog (read) | ✅ Complete | 90% (no write/admin API) | No | Low | Auth |
| Party Likes | ✅ Complete | 100% | No | Maintain | Party (create) |
| Party Create/Discover/Show/Membership/Lifecycle | ✅ Complete | 100% | No | Maintain | Auth, Game Catalog |
| User Profile | 🟡 Partial | 40% (view/edit only) | No — extend only | Low | Auth |
| Token Bundles (catalog + purchase) | 🟡 Partial | 80% (list + purchase; no `show`, no real payment gateway) | No — extend only | Low | Wallet API |
| Sponsorship | 🟡 Partial | 10% (schema columns only) | No | Deferred | Party |
| Horizon / Queue | 🟡 Partial | 20% (installed, inert; gate is `local`-only) | Yes — small (gate + first job) | High (Sprint 5) | — |
| Wallet Ledger Engine | ✅ Complete | 100% internal / exposed via read API | No | Maintain | Auth |
| Wallet API (routes/controller) | ✅ Complete | 100% read + token-bundle top-up write path | No | Maintain | Wallet Ledger Engine |
| CI/CD Pipeline | ⬜ Missing | 0% | N/A — net new (infra) | High (carried over from Sprint 1) | — |
| Marketplace (purchase/inventory) | ✅ Complete | Token bundle purchase (top-up) + pack purchase/inventory/ownership both done | No | Maintain | Wallet API, Token Bundles, Pack Catalog |
| Domain Events & Listeners | ⬜ Missing | 0% | N/A — net new | **High (Sprint 5, enabling infra)** | Existing services (Wallet, Party, PartyMembership, Purchases) to retrofit dispatch calls into |
| Game Engine (rounds/turns/timers/scoring) | ⬜ Missing | 0% | N/A — net new | High (Sprint 6–7) | Party Lifecycle, Pack Catalog, Domain Events |
| Realtime (Reverb) | ⬜ Missing | 0% | N/A — net new | Medium (Sprint 8) | Domain Events, Game Engine |
| Notifications | ⬜ Missing | 0% | N/A — net new | Medium (Sprint 9) | Domain Events, Queue activation |
| Friends / Social Graph | ⬜ Missing | 0% | N/A — net new | Medium (Sprint 10) | Auth (Users) |
| Admin Panel | ⬜ Missing | 0% | N/A — net new | Medium (Sprint 11) | Users, Parties, Wallet, Catalog (data to administer) |
| Analytics / Observability | ⬜ Missing | 0% | N/A — net new | Medium (Sprint 12) | Domain Events |
| AI Host ("Yowi") | ⬜ Missing | 0% | N/A — net new | Medium (Sprint 13, narrow scope) | Domain Events, Realtime |
| Chat / Messaging | ⬜ Missing | 0% | N/A — net new | Deferred | Friends, Realtime |
| Voice/Video (LiveKit) | ⬜ Missing | 0% | N/A — net new | Deferred | Realtime |
| Moderation / Trust & Safety | ⬜ Missing | 0% | N/A — net new | Deferred | Chat, Friends |
| Creator Economy | ⬜ Missing | 0% | N/A — net new | Deferred | Marketplace |
| Corporate / Multi-Tenant / Enterprise | ⬜ Missing | 0% | N/A — net new (schema-wide `tenant_id` retrofit) | Deferred | Admin, all core modules |
| Internationalization | ⬜ Missing | 0% | N/A — net new | Deferred | — |

---

## Notes

- **"Needs Refactor?"** answers whether *existing* code must change. It's `N/A` for modules with zero code today — those need net-new construction, not refactoring. Only three modules currently need refactor-level touch-up: the Wallet Ledger's `UserResource` stub, the Horizon gate/queue activation, and (implicitly) every existing service that will later need domain-event dispatch calls added (tracked as a Domain Events dependency, not a refactor of the target module itself).
- **No module needs a rewrite.** Every "Needs Refactor?: Yes" item is a small, isolated, additive fix — consistent with `docs/audit/TECHNICAL_DEBT.md`'s finding that nothing shipped so far is broken, only incomplete or unexposed.
- **Complete % is per-module scope**, not weighted by lines of code or doc page count — e.g. Game Catalog is 90% because only an admin/write path is missing, while Wallet Ledger is 100% internally complete but contributes 0% of its value until the API layer exists (tracked as a separate row).
- Full per-module evidence: `docs/audit/MODULE_STATUS.md`. Full dependency rationale and sprint sequencing: `docs/implementation/IMPLEMENTATION_ORDER.md`.
