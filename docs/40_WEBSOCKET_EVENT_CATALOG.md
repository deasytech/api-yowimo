# Yowimo WebSocket Event Catalog

**Version:** 1.0.0

**Status:** Realtime Communication Specification

**Priority:** CRITICAL

**Owner:** Platform Engineering

**Realtime Stack**

Laravel Reverb

Redis

LiveKit

Laravel Broadcasting

WebSockets

**Depends On**

- 08_GAME_ENGINE.md
- 09_REALTIME_ARCHITECTURE.md
- 11_AI_HOST_ARCHITECTURE.md
- 22_BACKEND_SERVICE_CATALOG.md
- 39_REST_API_REFERENCE.md

---

# Purpose

This document defines every realtime event exchanged between the backend and connected clients.

It serves as the single source of truth for

- Event Names
- Payloads
- Channels
- Authentication
- Ordering
- Reliability
- Versioning
- Security

Every broadcast event must be documented here.

---

# Realtime Philosophy

REST is for persistence.

WebSockets are for live state.

Never use WebSockets to permanently store data.

Persist first.

Broadcast second.

---

# Event Lifecycle

```text
Client Action

↓

REST API

↓

Service

↓

Database

↓

Domain Event

↓

Broadcast Event

↓

WebSocket

↓

Connected Clients
```

---

# Event Naming Convention

Past tense.

Examples

```
party.created

party.started

player.joined

player.left

wallet.updated

notification.created
```

Never use verbs like

```
createParty

joinRoom

sendReaction
```

---

# Event Versioning

Every event includes

```json
{
    "event": "party.started",
    "version": "1.0.0"
}
```

---

# Payload Structure

Every event follows

```json
{
    "event": "",
    "version": "1.0.0",
    "timestamp": "",
    "tenant_id": "",
    "data": {}
}
```

---

# Channel Types

Public

Private

Presence

Encrypted (Future)

---

# Channel Naming

Public

```
public.games
```

Private

```
private.user.{id}
```

Presence

```
presence.party.{party_id}
```

Organization

```
private.organization.{organization_id}
```

Creator

```
private.creator.{creator_id}
```

---

# Authentication

Private and Presence channels require

JWT

Tenant Validation

Permission Check

---

# CONNECTION EVENTS

---

## socket.connected

Purpose

Client connected.

---

## socket.disconnected

Purpose

Client disconnected.

---

## socket.reconnected

Purpose

Automatic reconnect.

---

## socket.error

Purpose

Connection error.

---

# PARTY EVENTS

---

## party.created

Broadcast

After successful creation.

Channel

```
private.user.{host}
```

Payload

```json
{
    "party_id": "...",
    "title": "Friday Night"
}
```

---

## party.updated

Broadcast when

Settings

Title

Visibility

Players

change.

---

## party.deleted

Broadcast

Party removed.

---

## party.started

Broadcast

Host starts game.

Payload

```json
{
    "party_id": "...",
    "started_at": "..."
}
```

---

## party.paused

---

## party.resumed

---

## party.completed

---

## party.cancelled

---

# PLAYER EVENTS

---

## player.joined

Broadcast

Player joins.

Payload

```json
{
    "user": {
        "id": "",
        "display_name": "",
        "avatar": ""
    }
}
```

---

## player.left

---

## player.ready

---

## player.unready

---

## player.kicked

Host only.

---

## player.reconnected

---

## player.disconnected

---

## player.typing

Used for chat.

---

## player.presence.updated

Updates

Online

Away

Offline

---

# GAME EVENTS

---

## game.started

---

## game.round.started

Payload

```json
{
    "round": 1
}
```

---

## game.round.completed

---

## game.turn.started

---

## game.turn.completed

---

## game.card.revealed

Payload

```json
{
    "card_id": "",
    "category": "truth"
}
```

---

## game.challenge.completed

---

## game.challenge.skipped

---

## game.finished

---

# TIMER EVENTS

---

## countdown.started

---

## countdown.updated

Broadcast

Every second.

Example

```json
{
    "remaining": 24
}
```

---

## countdown.finished

---

# SCORE EVENTS

---

## score.updated

---

## leaderboard.updated

---

## winner.announced

---

# REACTION EVENTS

---

## reaction.sent

Payload

```json
{
    "emoji": "🔥",
    "user_id": "..."
}
```

---

## reaction.removed

---

## reaction.burst

Multiple reactions.

---

# CHAT EVENTS

---

## message.created

---

## message.edited

---

## message.deleted

---

## message.read

---

## message.reaction

---

# VOICE EVENTS

LiveKit related.

---

## voice.joined

---

## voice.left

---

## voice.muted

---

## voice.unmuted

---

## voice.speaking

Broadcast

Current speaker.

---

# VIDEO EVENTS

---

## video.started

---

## video.stopped

---

## video.camera.changed

---

## video.screen.shared

Future.

---

# AI EVENTS

---

## ai.host.generated

AI generated message.

---

## ai.story.generated

---

## ai.summary.generated

---

## ai.translation.completed

---

## ai.recommendation.generated

---

# WALLET EVENTS

---

## wallet.updated

Payload

```json
{
    "balance": 500
}
```

---

## reward.granted

---

## reward.claimed

---

## purchase.completed

---

## purchase.failed

---

# MARKETPLACE EVENTS

---

## marketplace.purchase.completed

---

## inventory.updated

---

## creator.sale.completed

---

# NOTIFICATION EVENTS

---

## notification.created

---

## notification.read

---

## notification.deleted

---

# REFERRAL EVENTS

---

## referral.completed

---

## referral.reward.granted

---

# MODERATION EVENTS

---

## report.created

Admin.

---

## moderation.action

---

## user.suspended

---

## user.banned

---

# ORGANIZATION EVENTS

---

## employee.joined

---

## employee.left

---

## event.started

---

## event.completed

---

# SPONSOR EVENTS

---

## campaign.started

---

## campaign.completed

---

## sponsor.reward.claimed

---

# SYSTEM EVENTS

---

## maintenance.started

---

## maintenance.completed

---

## deployment.completed

---

## feature.enabled

---

## feature.disabled

---

# CHANNEL PERMISSIONS

Public

Everyone

Private

Authenticated Owner

Presence

Authenticated Members

Organization

Organization Members

Admin

Administrators

---

# Event Ordering

Critical events

Wallet

Purchases

Scores

must preserve ordering.

Use sequential processing.

---

# Delivery Guarantees

Critical Events

At Least Once

Chat

At Most Once

Typing

Best Effort

Countdown

Best Effort

---

# Duplicate Protection

Every event includes

```
event_id
```

Clients ignore duplicates.

---

# Replay

Future

Support replay from

Last Event ID.

---

# Compression

Enable payload compression for

Large Messages

Leaderboard

Analytics

---

# Rate Limits

Typing

10/sec

Reactions

20/sec

Chat

30/min

Broadcasts

Configurable

---

# Monitoring

Track

Connected Clients

Dropped Connections

Reconnects

Average Latency

Broadcast Time

Delivery Success

---

# Failure Handling

If broadcast fails

Retry

↓

Queue

↓

Dead Letter Queue

↓

Alert

---

# Security

Never broadcast

Private Wallet Data

Secrets

Payment Information

Personal Identifiers

Admin Data

---

# Logging

Every broadcast logs

Event

Channel

User

Tenant

Latency

Payload Size

Result

---

# Future Events

```
guild.created

tournament.started

achievement.unlocked

badge.earned

season.started

season.completed

plugin.installed

developer.webhook

live.event.started

ar.session.started
```

---

# Claude Code Instructions

When implementing realtime events:

1. Persist data before broadcasting.
2. Use standardized event names.
3. Broadcast only minimal payloads.
4. Protect private channels with authorization.
5. Include event version and timestamp.
6. Avoid duplicate broadcasts.
7. Monitor delivery latency.
8. Update this catalog whenever a new event is introduced.

---

# Acceptance Criteria

The WebSocket Event Catalog is complete when:

- Every broadcast event is documented.
- Payloads are standardized.
- Channels are secured.
- Event ordering is defined.
- Delivery guarantees are documented.
- Monitoring captures realtime health.
- New events are added through documented processes.

---
