# Yowimo Engineering Handbook

**Version:** 1.0.0

**Status:** Living Engineering Specification

**Owner:** Yowimo Engineering

**Last Updated:** 2026

---

# Welcome

Welcome to the official engineering handbook for **Yowimo**.

This repository contains the architectural, technical, and implementation specifications for the Yowimo platform.

Unlike traditional documentation, this handbook is the **single source of truth** for every engineering decision made throughout the lifetime of the platform.

Every developer, AI coding assistant, architect, DevOps engineer, QA engineer, and future CTO should use this documentation before making changes to the codebase.

---

# What is Yowimo?

Yowimo is a social multiplayer entertainment platform built around real-time party experiences.

The platform combines:

- Multiplayer party games
- Social networking
- AI-powered hosting
- Video parties
- Hybrid (physical + virtual) gameplay
- Token economy
- Marketplace
- Sponsorship system
- Corporate events
- TV experiences
- Community engagement

Yowimo is **not** simply a game.

It is an ecosystem.

---

# Engineering Philosophy

The platform is designed around five core principles.

## 1. Server Authoritative

The backend owns all critical game logic.

The frontend is responsible only for presentation.

Examples:

- Game state
- Timer
- Wallet balance
- Scores
- Rewards
- Card selection
- Results

must always be decided by the backend.

---

## 2. Event Driven

Every meaningful action should produce an event.

Examples include:

- PartyCreated
- PartyStarted
- WalletCredited
- CardDrawn
- RoundCompleted
- RewardGranted

Events become reusable across analytics, notifications, achievements, and leaderboards.

---

## 3. Modular

Each domain must remain isolated.

Examples:

Wallet must never know how the Marketplace works.

Marketplace must never calculate Rewards.

Game Engine must never send Push Notifications directly.

Communication should occur through services, events, or queues.

---

## 4. Scalable

Every architectural decision should support future scaling.

Examples include:

- Horizontal scaling
- Queue workers
- CDN integration
- Multiple application servers
- Read replicas
- Object storage
- Stateless APIs

---

## 5. Testable

Every feature must be testable independently.

Business logic belongs inside services.

Controllers remain thin.

Every API endpoint requires automated tests.

---

# Technology Stack

## Backend

Laravel 12

PHP 8.4+

PostgreSQL

Redis

Laravel Reverb

Laravel Horizon

Laravel Scheduler

Laravel Sanctum (internal services only)

Clerk Authentication

---

## Realtime

Laravel Reverb

Presence Channels

Private Channels

Broadcast Events

---

## Media

LiveKit

Cloudflare Stream (future)

Object Storage

---

## Queue

Redis

Laravel Horizon

---

## Storage

Amazon S3

Cloudflare R2 (future)

---

## Mobile

React Native

Expo

NativeWind

---

## AI

OpenAI

Future provider abstraction layer

---

# Engineering Rules

Every pull request must satisfy the following.

✓ Feature Tests

✓ Unit Tests

✓ Form Requests

✓ Policies

✓ Resources

✓ Service Layer

✓ Database Transactions

✓ Logging

✓ Exception Handling

✓ Documentation Updates

---

# Repository Structure

```text
app/

Console/

Events/

Exceptions/

Http/

Jobs/

Listeners/

Mail/

Models/

Notifications/

Observers/

Policies/

Providers/

Repositories/

Rules/

Services/

Traits/

ValueObjects/

database/

docs/

routes/

tests/
```

---

# Documentation Structure

Every document in `/docs` represents one domain of the platform.

Developers should always consult the relevant document before making changes.

---

# Claude Code Workflow

Before implementing any feature, Claude Code must:

1. Read this handbook.

2. Read the relevant domain document.

3. Audit the existing implementation.

4. Compare existing code with documentation.

5. Extend existing code.

6. Never duplicate functionality.

7. Never recreate existing migrations.

8. Never rewrite completed architecture unless necessary.

---

# Git Workflow

Each implementation phase follows the same lifecycle.

```text
Audit

↓

Plan

↓

Implement

↓

Tests

↓

Review

↓

Documentation

↓

Commit

↓

Tag
```

Recommended commit format:

```text
feat(wallet): implement ledger transactions

feat(game): add round engine

feat(reverb): broadcast party events
```

---

# Architecture First

Yowimo follows an architecture-first development model.

Every significant feature must be designed before implementation.

Engineering documentation takes precedence over code.

If code contradicts documentation, the discrepancy should be reviewed before additional work continues.

---

# Living Document Policy

This handbook is intended to evolve with the platform.

Whenever architecture changes:

- Update the relevant document.
- Record the reason for the change.
- Preserve backwards compatibility where possible.
- Document migration strategies for breaking changes.

---

# Success Criteria

The handbook is considered successful when:

- A new engineer can understand the platform without external explanation.
- Claude Code can implement new features by following the documentation.
- Architectural consistency is maintained across the codebase.
- Future contributors can extend the platform without introducing duplication or unnecessary coupling.
