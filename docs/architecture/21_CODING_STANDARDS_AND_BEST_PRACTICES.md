# Yowimo Coding Standards & Engineering Best Practices

**Version:** 1.0.0

**Status:** Mandatory Engineering Standard

**Priority:** CRITICAL

**Owner:** CTO / Engineering

**Applies To**

- Backend
- React Native
- React Web
- Admin Panel
- AI Services
- Infrastructure
- DevOps
- Testing

---

# Purpose

This document defines how code is written across Yowimo.

Every engineer should be able to open any file and immediately recognize the structure.

Consistency is more important than personal preference.

---

# Engineering Philosophy

Code should be

Readable

Predictable

Testable

Modular

Maintainable

Secure

Extensible

Performance Conscious

---

# Golden Rule

Write code for humans first.

Computers will execute it.

Engineers will maintain it.

---

# SOLID Principles

Every implementation should respect

Single Responsibility Principle

Open Closed Principle

Liskov Substitution

Interface Segregation

Dependency Inversion

---

# Clean Architecture

Always prefer

```text
Controller

↓

Service

↓

Repository

↓

Model
```

Never

```text
Controller

↓

Model

↓

Database
```

Controllers never contain business logic.

---

# Dependency Injection

Always inject dependencies.

Good

```php
public function __construct(
    WalletService $wallet
)
```

Avoid

```php
new WalletService()
```

---

# Services

Every business domain has a Service.

Examples

WalletService

GameService

PartyService

MarketplaceService

RewardService

NotificationService

AIService

SponsorService

---

# Service Rules

A Service should

Have one responsibility

Be reusable

Be testable

Never depend on HTTP

Never return views

---

# Repositories

Repositories abstract persistence.

Never query Eloquent directly inside services.

---

Good

```text
WalletService

↓

WalletRepository
```

---

# Controllers

Controllers only

Validate

Authorize

Call Service

Return Response

Maximum

```
50 Lines
```

---

# Validation

Always use

Form Requests

Never validate inside controllers.

---

# Policies

Authorization belongs in Policies.

Never

```php
if($user->isAdmin)
```

inside controllers.

Always

```php
$this->authorize(...)
```

---

# Models

Models represent data.

Models should NOT contain

Business Logic

Notification Logic

AI Logic

Payment Logic

Game Logic

---

# Traits

Use traits only for

Reusable behavior.

Avoid trait abuse.

---

# Enums

Prefer Enums over strings.

Bad

```php
$status = "active";
```

Good

```php
PartyStatus::ACTIVE
```

---

# Naming

Classes

PascalCase

```
WalletService
```

Variables

camelCase

```
currentBalance
```

Constants

UPPER_CASE

```
MAX_PLAYERS
```

Database

snake_case

```
wallet_transactions
```

---

# Method Names

Good

```
createParty()

joinParty()

creditWallet()

generateHighlights()
```

Avoid

```
run()

process()

handleStuff()

doEverything()
```

---

# Function Size

Ideal

```
10-20 Lines
```

Maximum

```
40 Lines
```

Extract methods instead.

---

# Class Size

Ideal

```
<300 Lines
```

Split large classes.

---

# Nesting

Avoid

```php
if

↓

if

↓

foreach

↓

if

↓

while
```

Prefer early returns.

---

# Early Returns

Good

```php
if (!$party) {
    return;
}
```

---

# Comments

Comment

Why

Not

What

Bad

```php
// increment counter
```

Good

```php
// Prevent duplicate reward claims during retries
```

---

# Magic Numbers

Never

```php
if ($tokens > 17)
```

Always

```php
MAX_FREE_REWARD
```

---

# Configuration

Never hardcode

API Keys

URLs

Timeouts

Limits

Always use config.

---

# Error Handling

Use

Domain Exceptions

Validation Exceptions

Custom Exceptions

Never swallow errors.

---

# Logging

Always use structured logs.

Never

```php
Log::info("Error")
```

Prefer

```php
Log::info(
    'wallet.credit',
    [...]
)
```

---

# Events

Business actions emit events.

Never call unrelated services directly.

Good

Wallet Credited

↓

RewardGranted Event

↓

Notification Listener

↓

Analytics Listener

---

# Transactions

Use DB Transactions for

Wallet

Marketplace

Rewards

Sponsors

Purchases

---

# Idempotency

Critical operations must be repeatable.

Never double-credit.

Never double-charge.

---

# Async First

Heavy work belongs in queues.

Never

Generate AI

Process Video

Send Email

Inside HTTP requests.

---

# Testing

Every Service requires

Unit Tests

Feature Tests

Critical paths

95% Coverage

---

# API Responses

Always use

API Resources.

Never return Eloquent Models directly.

---

# Versioning

Never break existing APIs.

Introduce

v2

instead.

---

# Security

Never trust client input.

Always validate.

Always authorize.

Always sanitize uploads.

---

# React Native Standards

Use

TypeScript

Functional Components

Hooks

React Query

NativeWind

React Hook Form

Zod

Avoid

Class Components

Inline Business Logic

Deep Prop Drilling

---

# React Folder Structure

```
features/

components/

hooks/

services/

stores/

types/

utils/

```

---

# Component Rules

Components should

Render UI

Receive Props

Emit Events

Business logic belongs elsewhere.

---

# State Management

Global

Zustand

Server

React Query

Forms

React Hook Form

Local

useState

---

# Styling

NativeWind Only

Avoid inline styles except

Animations

Dynamic Dimensions

Transforms

---

# API Layer

Never call fetch directly.

Always use

API Client

↓

Typed Service

↓

React Query

---

# Imports

Order

React

Third Party

Internal

Styles

---

# File Naming

Components

```
PartyCard.tsx
```

Hooks

```
useWallet.ts
```

Services

```
wallet.service.ts
```

Types

```
wallet.types.ts
```

---

# AI Standards

Never call LLMs directly.

Always use

AI Orchestrator.

Prompts are versioned.

Responses are validated.

---

# Git Standards

Every PR

Tests

Lint

Type Check

Code Review

CI

Required.

---

# Documentation

Every major module contains

README.md

Architecture Notes

Examples

---

# Technical Debt

Allowed only if

Documented

Tracked

Scheduled

Approved

---

# Performance

Measure before optimizing.

Avoid premature optimization.

---

# Code Review Checklist

Reviewer verifies

Correctness

Security

Performance

Readability

Tests

Architecture

Documentation

---

# Engineering Values

Prefer clarity over cleverness.

Prefer composition over inheritance.

Prefer explicit over implicit.

Prefer small modules over giant classes.

Prefer events over tight coupling.

Prefer services over controllers.

Prefer interfaces over implementations.

Prefer architecture over shortcuts.

---

# Claude Code Instructions

When generating code:

1. Follow this document unless explicitly instructed otherwise.
2. Never place business logic inside controllers or components.
3. Use dependency injection.
4. Use events for cross-domain communication.
5. Write tests alongside new functionality.
6. Keep functions and classes small.
7. Prefer readability over clever optimizations.
8. Respect naming conventions.
9. Generate production-ready code.

---

# Acceptance Criteria

The engineering standards are successful when:

- Every repository follows the same conventions.
- Code reviews focus on logic instead of formatting.
- New engineers onboard quickly.
- Technical debt is minimized.
- Architecture remains consistent as the team grows.

---
