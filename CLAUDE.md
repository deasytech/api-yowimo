# Yowimo API Engineering Rules

These instructions apply to every task in this repository unless the user explicitly overrides them.

---

# PRIMARY OBJECTIVE

Only implement the change requested.

Do not refactor unrelated code.

Do not rewrite working features.

Do not "clean up" files unless requested.

Do not introduce architectural changes unless they are required to solve the requested problem.

---

# PRESERVE EXISTING BEHAVIOR

Assume every existing feature is production code.

Do not modify working functionality.

Never change business logic outside the requested scope.

Never remove validation.

Never rename routes.

Never rename controllers.

Never rename services.

Never rename database fields.

Never rename models.

Never change APIs.

Never change request payloads.

Never change response payloads.

Never change authentication.

Never change permissions.

Never change middleware.

Never change queue behaviour.

Never change event behaviour.

Never change notifications.

Never change scheduled tasks.

Unless the prompt explicitly requests it.

---

# MINIMIZE CHANGES

Modify the smallest amount of code possible.

Prefer editing existing methods.

Avoid rewriting entire files.

Avoid unnecessary formatting changes.

Avoid changing import order.

Avoid changing indentation unrelated to the task.

Avoid changing comments unless needed.

---

# DO NOT BREAK WORKING FEATURES

Before modifying code:

Identify every affected feature.

Reason about possible side effects.

Avoid changing shared utilities unless absolutely necessary.

If a shared utility must change:

Explain why.

List every affected location.

Keep backward compatibility.

---

# DEBUGGING

When fixing bugs:

Identify the root cause.

Do not apply speculative fixes.

Do not suppress exceptions.

Do not disable validation.

Do not comment out code.

Do not remove functionality to make tests pass.

---

# LARAVEL RULES

Follow Laravel conventions.

Use existing services.

Use existing Form Requests.

Use existing Resources.

Respect dependency injection.

Do not duplicate logic.

Reuse existing helpers.

---

# REACT / EXPO RULES

Never redesign UI unless requested.

Never change navigation.

Never rename screens.

Never remove state.

Never change API calls unless required.

Never modify unrelated components.

---

# DATABASE RULES

Never modify migrations unless requested.

Never change schema unless required.

Never remove columns.

Never change foreign keys.

Never delete data.

Never modify production seeders.

---

# API RULES

Do not change:

URLs

HTTP methods

Authentication

Headers

Response structure

Status codes

Unless requested.

---

# SECURITY

Never weaken security.

Never remove authorization.

Never disable CSRF.

Never bypass validation.

Never remove encryption.

Never expose secrets.

---

# PERFORMANCE

Do not introduce N+1 queries.

Avoid unnecessary database queries.

Avoid loading unnecessary relationships.

Avoid duplicate work.

---

# TESTING

After every change:

Identify possible regressions.

Update tests only if behaviour changed intentionally.

Never modify tests just to make them pass.

---

# OUTPUT FORMAT

Before writing code:

Explain:

1. Root cause
2. Files to change
3. Why those files

Then implement only the requested change.

Finally summarize:

Files modified

What changed

What was intentionally left untouched

Potential side effects

Regression risks

---

# IF THE REQUEST IS AMBIGUOUS

Ask before making assumptions.

Do not guess.

Do not invent business logic.

---

# GOLDEN RULE

If it already works,

DO NOT CHANGE IT.
