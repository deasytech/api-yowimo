# Architecture Rules — AI Coding Constitution

Extracted from all 61 files in `docs/architecture/`. These are the non-negotiable rules the documentation set imposes on every backend change. Treat this file as the constitution; treat `docs/architecture/*` as the detailed law behind each clause.

**Precedence note:** the current codebase (see `.claude/PROJECT_CONTEXT.md`, `docs/audit/`) has not yet adopted every pattern below (e.g., no Repository/DTO layer, no domain events exist yet). Where a rule below describes a target pattern not yet present in the code, **do not unilaterally introduce it mid-task** — new base folders/patterns require explicit approval (`AGENTS.md`). Apply these rules fully whenever building or extending a module that already follows them (Wallet, Party, Clerk services); when a rule conflicts with existing sibling-file convention in an unmigrated area, match the existing convention and flag the conflict rather than silently mixing patterns. `docs/implementation/IMPLEMENTATION_ORDER.md` governs *when* each pattern gets formally adopted.

---

## 1. Layering — one-way dependency flow

- **Controllers stay thin.** Authorize (Policy) → validate (Form Request) → delegate to a Service. No business logic, no raw Eloquent queries beyond a trivial lookup. (`21`, `53`)
- **Services own all business logic.** Every domain operation, every transaction, every invariant lives in a Service — never in a Controller, Model, or Job. (`00`, `21`, `22`)
- **Repositories own persistence.** Services depend on Repositories for queries/writes; Services never build complex query logic inline. (`21`, `22`, `53`)
- **Models stay dumb.** Casts, relationships, scopes, and simple self-invariants only (e.g. append-only enforcement) — no business logic, no cross-domain calls.
- **Dependency direction is one-way:** Controller → Service → Repository → Model. Never Service → Controller. Never Repository → Service. (`22`)
- **Modules are isolated.** One domain never reaches into another's internals — Wallet must never know how Marketplace works; Marketplace must never calculate Rewards; Game Engine must never send push notifications directly. Cross-module communication happens only through a Service's public methods, events, or queues. (`00`)

## 2. Data contracts

- **Always use DTOs** to move structured data into and out of Services — don't thread raw request arrays deep into business logic. (`53`)
- **Always use Form Requests** for input validation; controllers never validate inline.
- **Always use API Resources** for output; controllers never return raw Models or bare arrays.
- **Always use database transactions** (`DB::transaction`) around any multi-step write — anything touching money, a uniqueness constraint, or a counter. (`12`, `45`)
- **Single source of truth, never duplicate state.** Derived/cached fields (e.g. a wallet balance) must be recomputable from their source of truth, never treated as authoritative themselves. (`47`)

## 3. Wallet & financial data — the strictest rules in the whole spec

- **Never modify wallet balances directly.** All balance changes go through the WalletService ledger (`credit`, `debit`, `reserve`, `release`, `refund`) — the cached `balance` column is derived, the ledger is the source of truth. This exact rule is repeated across at least 8 documents (`03`, `04`, `06`, `12`, `22`, `38`, `41`, `52`) — it is the single most reinforced invariant in the entire corpus.
- **Ledger, transaction, and audit-log rows are append-only.** Never update or delete a financial or audit record.
- **Every purchase/payment-style write requires an `Idempotency-Key`**, enforced server-side, not just documented. (`05`, `12`, `45`)
- **Reservation before commitment:** pending purchases reserve funds (reducing available balance) and release/settle explicitly on success or failure — never a blind debit-then-hope-it-works. (`12`)

## 4. Authorization & security

- **Use Policies for every authorization decision** — never an inline `if ($user->role === ...)` check in a controller or service.
- **Authentication is delegated to Clerk.** Never implement local password/session-based auth; the app only verifies JWTs and provisions/syncs local user records. (`06`)
- **Rate limit every public endpoint category** per its documented budget (auth, party creation, purchases, chat, etc.). (`05`, `06`)
- **RBAC/ABAC via Gates/Policies only** — no ad hoc permission logic scattered across controllers. (`43`)

## 5. Events, queues & async work

- **Every meaningful state change fires a domain event**, named `noun.verb` / PastTense, **only after the database transaction commits** — never before, never speculatively. (`07`, `41`, `45`)
- **Always queue expensive or non-critical work** — notifications, emails, analytics, AI calls, media/highlight processing. Nothing non-critical runs synchronously in the request cycle. (`10`, `22`)
- Wallet-critical operations may run immediately (not queued) but must still be transactional and idempotent — "immediate" is not an excuse to skip the ledger rules in §3. (`07`)

## 6. Realtime & game state

- **Server is authoritative.** Game state, timers, scores, card selection, rewards, and results are always decided by the backend — the client only presents what the server sends. (`00`, `08`, `09`)
- **Persist before broadcast.** A realtime/broadcast event never fires for state that hasn't been durably committed yet. (`45`, `47`)
- Channel and event names follow the documented convention (`presence-*`/`private-*` channel prefixes, `noun.verb` event names) — don't invent a new naming scheme per feature. (`09`, `40`)

## 7. API contract

- **Version every endpoint** under `/api/{version}` — never introduce a breaking change to an already-shipped version. (`02`, `05`)
- **One consistent response envelope** (success/message/data/meta, or the documented error shape) for every endpoint — never a bespoke ad hoc shape. (`05`)
- **Every endpoint ships with automated tests.** No untested endpoint merges. (`00`, `19`, `56`)

## 8. Observability & audit

- **Use structured logging** (contextual fields, not string concatenation) for anything touching wallet, auth, or security. (`15`, `57`)
- **Audit-log every financial or admin-sensitive action**: actor, action, entity, old/new values, IP, timestamp. (`16`, `52`)

## 9. Code shape discipline

- Keep controllers, functions, and classes small — a growing controller/service is a signal to extract a new Service/Action, not to keep appending. (`21`, `53`)

---

**When in doubt:** re-read the relevant numbered doc in `docs/architecture/` for the full rationale, then check `docs/audit/MODULE_STATUS.md` to see whether the module you're touching has already adopted these patterns or is still pre-migration.
