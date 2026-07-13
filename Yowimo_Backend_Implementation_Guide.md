# Yowimo Backend Implementation Guide

## Overview

This guide is the single source of truth for Claude Code.

### Workflow

1.  Audit existing implementation.
2.  Compare against this phase.
3.  Extend existing code.
4.  Never duplicate code.
5.  Commit after each completed phase.

## Master Rules

-   Laravel 12
-   PHP 8.4+
-   Thin controllers
-   Service layer
-   Form Requests
-   API Resources
-   Policies
-   Feature tests
-   Database transactions
-   Queue long-running work
-   Reverb for realtime
-   LiveKit for media
-   Clerk authentication
-   PostgreSQL
-   Redis queues

------------------------------------------------------------------------

# Phase 1 -- Audit, Catalogs & Discovery

## Audit

-   Inspect migrations, models, routes, services, tests.
-   Produce Complete / Partial / Missing report.
-   Do not rewrite completed work.

## Deliverables

-   Game Types
-   Packs
-   Cards
-   Marketplace
-   Token Bundles
-   Party CRUD
-   Discovery feed

## Required

-   Migrations
-   Models
-   Factories
-   Seeders
-   Policies
-   Resources
-   Requests
-   Tests
-   API docs

Acceptance: - Frontend mock data fully replaced.

------------------------------------------------------------------------

# Phase 2 -- Wallet & Payments

Audit existing implementation first.

Deliver: - Ledger-first wallet - Transactions - Token purchases -
Rewards - Sponsored balances - Ad reward pipeline - Idempotent payment
processing

Acceptance: - Balance derived from ledger. - Concurrency safe.

------------------------------------------------------------------------

# Phase 3 -- Parties & Lobby

Audit first.

Deliver: - Invitations - Scheduling - Presence - Ready state - Waiting
room - QR joining - TV pairing

Acceptance: - Complete party lifecycle.

------------------------------------------------------------------------

# Phase 4 -- Chat & Realtime

Audit first.

Deliver: - Reverb channels - Party chat - Emoji reactions - Voice
metadata - Notifications - Presence

Acceptance: - Horizontal scalable architecture.

------------------------------------------------------------------------

# Phase 5 -- Game Engine

Audit first.

First document the complete game state machine before coding.

Deliver: - Server authoritative rounds - Cards - Timer engine - Teams -
Scoring - MVP calculation

Acceptance: - Client never determines game outcome.

------------------------------------------------------------------------

# Phase 6 -- LiveKit & Hybrid

Audit first.

Deliver: - LiveKit rooms - Video - Audio - TV casting lifecycle - Hybrid
synchronization

Acceptance: - Laravel owns authorization. - LiveKit owns media.

------------------------------------------------------------------------

# Phase 7 -- Results & Rewards

Audit first.

Deliver: - Awards - Highlights - Party recap - Achievements -
Leaderboards - Notifications

Acceptance: - Results generated from persisted events.

------------------------------------------------------------------------

# Phase 8 -- Social & Production

Audit first.

Deliver: - Friends - Referrals - Sponsors - Analytics - Moderation -
Rate limiting - Monitoring - Production hardening

Acceptance: - Production ready release candidate.

## Git Workflow

After every phase:

1.  Run tests
2.  Static analysis
3.  Format
4.  Commit
5.  Tag milestone
