# Yowimo Realtime Architecture

**Version:** 1.0.0

**Status:** Living Engineering Specification

**Priority:** CRITICAL

**Owner:** Platform Engineering

**Depends On**

- 02_SYSTEM_ARCHITECTURE.md
- 05_API_STANDARDS.md
- 07_EVENT_CATALOG.md
- 08_GAME_ENGINE.md

---

# Purpose

Realtime communication is one of the core differentiators of Yowimo.

Players expect the experience to feel instantaneous whether they are:

- Sitting around the same table
- Joining remotely
- Watching on TV
- Participating through LiveKit
- Spectating
- Watching highlights
- Interacting with AI Host

This document defines how realtime communication works across the platform.

---

# Realtime Philosophy

Realtime is an extension of the Game Engine.

The Game Engine owns the truth.

Realtime distributes the truth.

Clients never own the truth.

```text
Game Engine

↓

Events

↓

Laravel Reverb

↓

Clients
```

---

# Primary Technologies

Laravel Reverb

↓

Broadcasting

↓

Redis

↓

Queues

↓

LiveKit

↓

React Native

---

# Responsibilities

Laravel Reverb

✓ Broadcast Events

✓ Presence

✓ Private Channels

✓ Party Synchronization

✓ Reactions

✓ Votes

✓ Timers

---

LiveKit

✓ Voice

✓ Video

✓ Screen Sharing (Future)

✓ Recording

✓ Audio Processing

---

Redis

✓ Presence

✓ Queues

✓ Cache

✓ Broadcast Driver

---

# High-Level Flow

```mermaid
flowchart LR

GameEngine

↓

DomainEvents

↓

BroadcastEvents

↓

LaravelReverb

↓

Players

Audience

TV

AIHost

Moderators
```

---

# Channel Types

Public

Private

Presence

---

## Public Channels

Visible to everyone.

Examples

```
announcements

public-events

leaderboard.global
```

---

## Private Channels

Require authentication.

Examples

```
wallet.{user}

notifications.{user}

profile.{user}
```

---

## Presence Channels

Track who is online.

Examples

```
party.{id}

room.{id}

livekit.{room}
```

---

# Presence Data

Each connected user provides

```json
{
    "user_id": "...",
    "name": "...",
    "avatar": "...",
    "role": "...",
    "joined_at": "..."
}
```

---

# Presence Lifecycle

User Connects

↓

Authenticate

↓

Join Presence

↓

Broadcast Join

↓

Heartbeat

↓

Disconnect

↓

Broadcast Leave

---

# Party Channel

Every party owns

```
presence-party.{party_uuid}
```

Events

```
Player Joined

Player Left

Ready

Typing

Reaction

Vote

Turn Started

Timer

Card Revealed
```

---

# Wallet Channel

```
private-wallet.{user_uuid}
```

Events

```
Wallet Credited

Wallet Debited

Reward Granted

Purchase Completed
```

---

# Notification Channel

```
private-notification.{user_uuid}
```

Events

```
Notification Created

Delivered

Read
```

---

# Game Channel

```
presence-game.{session_uuid}
```

Events

```
Game Started

Round Started

Turn Started

Card Drawn

Vote Opened

Vote Closed

Score Updated

Game Ended
```

---

# Broadcast Naming

Always

```
domain.action
```

Examples

```
turn.started

timer.updated

vote.closed

reaction.sent

reward.granted
```

---

# Broadcast Payload

Example

```json
{
    "type": "turn.started",

    "session_id": "...",

    "turn": 4,

    "player": "Alex",

    "timestamp": "..."
}
```

---

# Timer Synchronization

Server owns timer.

Clients never decrement independently.

Flow

```
Timer Started

↓

Server Clock

↓

Broadcast Remaining Time

↓

Clients Render
```

If client reconnects

↓

Receive Current Time

↓

Resume

---

# Live Reactions

Players send

```
Reaction
```

↓

Server validates

↓

Broadcast

↓

Animation

Reactions never affect gameplay.

---

# Live Voting

Vote

↓

Validate

↓

Persist

↓

Broadcast Updated Count

↓

Close

↓

Broadcast Winner

---

# Chat Architecture

Party Chat

Private Chat (Future)

Corporate Chat

Moderator Chat

Sponsor Chat

---

Message Flow

```text
Client

↓

API

↓

Persist

↓

Broadcast

↓

Clients
```

Messages are stored before broadcasting.

---

# Typing Indicators

Typing indicators are NOT persisted.

Broadcast only.

Expire after

```
5 seconds
```

---

# Read Receipts

Persist

Message ID

↓

Reader

↓

Timestamp

↓

Broadcast

---

# Voice Architecture

Voice handled entirely by LiveKit.

Laravel stores

Room Metadata

Participants

Permissions

Recording

Analytics

---

# Video Architecture

Video uses LiveKit.

Server controls

Room Creation

Tokens

Permissions

Recording

Participant Limits

---

# LiveKit Room Flow

```mermaid
flowchart LR

PartyCreated

↓

RoomCreated

↓

JoinToken

↓

LiveKit

↓

Voice

Video

Recording
```

---

# AI Host

AI Host joins party as virtual participant.

AI receives events.

Example

```
Turn Started

↓

Read Card

↓

Countdown

↓

Announce Winner
```

AI never owns game state.

---

# TV Mode

TV connects using

```
tv.{party}
```

Receives

Current Turn

Timer

Leaderboard

Highlights

Never sends gameplay actions.

---

# Hybrid Parties

Players

↓

Mobile

Remote Players

↓

LiveKit

Audience

↓

Reverb

TV

↓

Display

All synchronized.

---

# Connection Recovery

If socket disconnects

↓

Reconnect

↓

Authenticate

↓

Restore Presence

↓

Replay Missed State

↓

Continue

---

# Heartbeats

Heartbeat every

```
30 seconds
```

Failure

↓

Mark Offline

↓

Broadcast Leave

---

# Offline Handling

Temporary Disconnect

↓

Grace Period

↓

Reconnect

↓

Restore

Long Disconnect

↓

Player AFK

↓

AI Host Continues

---

# Conflict Resolution

Server always wins.

If client state differs

↓

Overwrite Client

Never merge gameplay state.

---

# Event Ordering

Maintain causal order.

```
Turn Started

↓

Card Revealed

↓

Timer Started

↓

Challenge Completed
```

Never reorder.

---

# Queue Strategy

Immediate

Turn Started

Card Drawn

Timer

Queued

Analytics

Emails

Highlights

AI Summary

Leaderboard Rebuild

---

# Scaling Strategy

Current

```
Single Reverb Server
```

↓

Redis

↓

Multiple Reverb Nodes

↓

Load Balancer

↓

Horizontal Scaling

---

# Presence Scaling

Redis stores

Presence

↓

Shared Across Nodes

Allows multiple websocket servers.

---

# Metrics

Track

Connected Users

Concurrent Parties

Messages/sec

Broadcast Latency

Reconnect Rate

Average Ping

Dropped Connections

---

# Failure Recovery

Reverb Crash

↓

Reconnect

↓

Restore Presence

↓

Replay Current State

Game Engine never loses state.

---

# Monitoring

Monitor

WebSocket Errors

Latency

Dropped Packets

Room Count

Broadcast Queue

Presence Count

Memory Usage

---

# Security

Authenticate every socket.

Authorize every channel.

Never expose:

Wallet

Private Messages

Moderator Data

Admin Data

Sponsor Analytics

without permission.

---

# Claude Code Instructions

When implementing realtime features:

1. Persist business state before broadcasting.
2. Broadcast only domain events.
3. Never trust client timers.
4. Use Presence Channels for parties.
5. Use Private Channels for personal data.
6. Rebuild client state after reconnect.
7. Queue expensive listeners.
8. Update this document when adding channels or broadcast events.

---

# Acceptance Criteria

Realtime architecture is complete when:

- All gameplay synchronization is server authoritative.
- Players reconnect seamlessly.
- Presence accurately reflects connected users.
- Voice and video integrate through LiveKit.
- Broadcast events remain lightweight.
- State consistency is preserved during failures.
- Horizontal scaling is supported.

---
