# Yowimo Event Catalog

**Version:** 1.0.0

**Status:** Living Engineering Specification

**Owner:** Platform Engineering

**Depends On**

- 00_READ_ME_FIRST.md
- 02_SYSTEM_ARCHITECTURE.md
- 03_DOMAIN_MODEL.md
- 04_DATABASE_ARCHITECTURE.md
- 05_API_STANDARDS.md
- 06_SECURITY_STANDARDS.md

---

# Purpose

Yowimo is an **Event-Driven Platform**.

Every important business action produces one or more Domain Events.

Those events are consumed independently by:

- Realtime
- Notifications
- Analytics
- Wallet
- Rewards
- AI
- Sponsors
- Marketplace
- Leaderboards

This architecture keeps modules loosely coupled while allowing the platform to scale naturally.

---

# Why Event Driven?

Traditional applications execute everything inside one request.

Example

```
User joins party

↓

Create Membership

↓

Send Push

↓

Broadcast

↓

Update Analytics

↓

Grant Reward

↓

Refresh Leaderboard

↓

Return Response
```

This makes APIs slow.

Instead:

```
User joins party

↓

PartyJoined Event

↓

Return Success

↓

Queue Handles Everything Else
```

The user gets an immediate response while the platform continues processing in the background.

---

# Event Principles

Every event must be:

✓ Immutable

✓ Past Tense

✓ Serializable

✓ Versionable

✓ Idempotent

✓ Business Meaningful

---

# Event Naming Convention

Use:

```
noun.verb
```

Examples

```
party.created

party.started

party.completed

wallet.credited

wallet.debited

reward.granted

purchase.completed

highlight.generated
```

Never

```
createParty

walletUpdate

start_game

notification1
```

---

# Event Categories

```
Authentication

Users

Friends

Party

Game

Wallet

Rewards

Marketplace

Sponsor

Realtime

LiveKit

Notifications

Analytics

AI

Admin
```

---

# Authentication Events

```
auth.registered

auth.logged_in

auth.logged_out

auth.email_verified

auth.password_reset

auth.account_deleted
```

Consumers

Analytics

Notifications

Audit Logs

---

# User Events

```
user.created

user.updated

user.avatar_updated

user.preferences_updated

user.deleted
```

Consumers

Analytics

Cache

Notifications

---

# Friendship Events

```
friend.requested

friend.accepted

friend.declined

friend.removed

friend.blocked
```

Consumers

Notifications

Analytics

---

# Party Events

```
party.created

party.updated

party.deleted

party.scheduled

party.opened

party.locked

party.started

party.paused

party.resumed

party.completed

party.cancelled

party.archived
```

Consumers

Realtime

Notifications

Analytics

AI Host

---

# Party Member Events

```
party.member_joined

party.member_left

party.member_ready

party.member_removed

party.host_changed
```

Consumers

Realtime

Presence

Analytics

---

# Invitation Events

```
invitation.created

invitation.sent

invitation.accepted

invitation.declined

invitation.expired
```

Consumers

Push

SMS

Email

Analytics

---

# Game Session Events

```
game.created

game.preparing

game.started

game.paused

game.resumed

game.completed

game.archived
```

Consumers

Realtime

Analytics

Highlights

---

# Round Events

```
round.started

round.completed

round.skipped
```

Consumers

Realtime

AI Host

Analytics

---

# Turn Events

```
turn.started

turn.timer_started

turn.timer_expired

turn.completed

turn.skipped
```

Consumers

Realtime

Voice Host

Audience

---

# Card Events

```
card.drawn

card.revealed

card.completed

card.skipped
```

Consumers

AI Narrator

Highlights

Analytics

---

# Vote Events

```
vote.opened

vote.cast

vote.closed

vote.counted
```

Consumers

Realtime

Leaderboard

Rewards

---

# Wallet Events

```
wallet.created

wallet.credited

wallet.debited

wallet.reserved

wallet.released
```

Consumers

Analytics

Notifications

Audit

---

# Transaction Events

```
transaction.created

transaction.completed

transaction.failed

transaction.refunded
```

Consumers

Wallet

Audit

Finance

Analytics

---

# Reward Events

```
reward.granted

reward.claimed

reward.expired
```

Consumers

Wallet

Notifications

Leaderboard

---

# Marketplace Events

```
product.created

product.updated

product.deleted

purchase.started

purchase.completed

purchase.refunded
```

Consumers

Wallet

Analytics

Inventory

---

# Sponsor Events

```
sponsor.created

sponsor.updated

sponsor.credited

sponsor.party_funded
```

Consumers

Wallet

Analytics

Notifications

---

# AI Events

```
ai.started

ai.finished

ai.voice_generated

ai.translation_completed

ai.summary_generated

ai.challenge_created
```

Consumers

Realtime

Analytics

Highlights

---

# LiveKit Events

```
room.created

room.opened

room.closed

participant.joined

participant.left

participant.muted

participant.unmuted

recording.started

recording.completed
```

Consumers

Analytics

Highlights

Notifications

---

# Notification Events

```
notification.created

notification.sent

notification.delivered

notification.failed

notification.read
```

Consumers

Analytics

Audit

---

# Highlight Events

```
highlight.created

highlight.generated

highlight.published

highlight.shared
```

Consumers

Social Feed

Analytics

Storage

---

# Analytics Events

```
analytics.party_completed

analytics.session_completed

analytics.purchase_completed

analytics.reward_granted
```

Consumers

Reporting

Dashboard

Recommendations

---

# Event Payload Standard

Every event contains

```json
{
    "id": "uuid",

    "type": "party.started",

    "version": 1,

    "occurred_at": "...",

    "actor_id": "...",

    "resource_type": "Party",

    "resource_id": "...",

    "metadata": {}
}
```

---

# Event Versioning

Example

```
v1

party.started
```

Future

```
v2

party.started
```

Consumers must ignore unknown fields.

---

# Event Flow Example

Player joins party

```mermaid
flowchart LR

Player

↓

PartyService

↓

party.member_joined

↓

Realtime

Notifications

Analytics

Presence

Reward Engine
```

Each consumer is independent.

---

# Purchase Flow

```mermaid
flowchart TD

Purchase

↓

purchase.started

↓

Wallet

↓

purchase.completed

↓

Reward Engine

↓

Notifications

↓

Analytics
```

---

# Game Flow

```mermaid
flowchart TD

Game Started

↓

Round Started

↓

Turn Started

↓

Card Drawn

↓

Challenge Completed

↓

Vote Counted

↓

Reward Granted

↓

Round Completed

↓

Game Completed
```

---

# Queue Strategy

Every event determines whether it should execute synchronously or asynchronously.

### Immediate

Authentication

Authorization

Validation

Wallet Lock

### Queued

Push Notifications

Emails

Leaderboard Updates

Analytics

Highlight Generation

AI Summary

Sponsor Reports

---

# Event Ordering

Events should preserve causal order.

Example

```
party.started

↓

round.started

↓

turn.started

↓

card.drawn
```

Never

```
card.drawn

↓

party.started
```

---

# Event Idempotency

Consumers must safely process duplicate events.

Example

RewardGranted

If already processed

↓

Ignore

Never double-credit a wallet.

---

# Broadcast Events

Some events are broadcast immediately.

Examples

```
turn.started

card.drawn

vote.opened

player.joined

reaction.sent
```

Broadcast via Laravel Reverb.

---

# Internal Events

Some events remain internal.

Examples

```
wallet.snapshot_created

analytics.updated

cache.invalidated
```

Never expose these to clients.

---

# Retry Policy

Queued consumers retry:

```
1

5

15

30

60
```

minutes

After maximum retries

↓

Dead Letter Queue

↓

Alert Engineering

---

# Dead Letter Queue

Failed events move into:

```
failed_jobs
```

Operations dashboard must expose:

Reason

Attempts

Stack Trace

Replay Button

---

# Event Correlation

Every event inherits

```
Correlation ID
```

allowing tracing across

HTTP

Queue

Realtime

Notifications

AI

---

# Event Ownership

Every event has one publisher.

Example

```
PartyService

↓

party.started
```

Never publish the same event from multiple modules.

---

# Event Consumers

One event may have many listeners.

Example

```
party.completed

↓

Wallet

↓

Rewards

↓

Highlights

↓

Analytics

↓

Leaderboard

↓

Notifications
```

Publisher never knows consumers.

---

# Saga Pattern

Complex workflows use orchestration.

Example

Purchase

```
Purchase Started

↓

Debit Wallet

↓

Grant Product

↓

Reward User

↓

Analytics

↓

Notification
```

If a step fails

↓

Compensation Logic

↓

Rollback

---

# Audit Integration

Critical events automatically create

Audit Logs

Examples

```
wallet.debited

reward.granted

purchase.completed

party.deleted
```

---

# Monitoring

Track

Event Throughput

Consumer Latency

Queue Time

Retry Count

Failure Rate

Dead Letters

---

# Claude Code Instructions

When implementing a feature:

1. Determine if the action represents a business event.
2. Publish one domain event after successful completion.
3. Keep event payloads immutable.
4. Never perform unrelated work inside the publisher.
5. Create listeners for downstream concerns.
6. Queue expensive listeners.
7. Version payloads when introducing breaking changes.
8. Update this catalog whenever a new event is added.

---

# Acceptance Criteria

The event architecture is considered complete when:

- Every important business action emits a domain event.
- Publishers are unaware of their consumers.
- Expensive work is processed asynchronously.
- Events are immutable and idempotent.
- Monitoring and replay mechanisms exist.
- Event naming remains consistent across the platform.

---
