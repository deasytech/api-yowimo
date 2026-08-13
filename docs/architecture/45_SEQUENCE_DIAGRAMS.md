# Yowimo Sequence Diagrams

**Version:** 1.0.0

**Status:** System Interaction Specification

**Priority:** CRITICAL

**Owner:** Platform Engineering

**Architecture**

Laravel

React Native

Reverb

LiveKit

Redis

PostgreSQL

**Depends On**

- 22_BACKEND_SERVICE_CATALOG.md
- 39_REST_API_REFERENCE.md
- 40_WEBSOCKET_EVENT_CATALOG.md
- 41_DOMAIN_EVENT_CATALOG.md
- 42_QUEUE_JOB_REFERENCE.md

---

# Purpose

This document illustrates how different components interact over time.

Sequence diagrams explain

- API flow
- Service interaction
- Database operations
- Queue dispatching
- Realtime broadcasting
- AI orchestration
- Wallet transactions
- Marketplace purchases

These diagrams serve as the implementation blueprint for developers.

---

# Diagram Legend

```
User

React Native

API

Controller

Service

Repository

Database

Queue

Reverb

LiveKit

AI

Notification

Analytics
```

---

# 1. User Registration

```mermaid
sequenceDiagram

actor User

participant App

participant Clerk

participant API

participant UserService

participant DB

participant Queue

participant Notification

User->>App: Register

App->>Clerk: Create Account

Clerk-->>App: JWT

App->>API: Complete Registration

API->>UserService: Create User

UserService->>DB: Save User

UserService->>Queue: CreateWalletJob

UserService->>Queue: WelcomeRewardJob

UserService->>Queue: WelcomeNotificationJob

Queue->>Notification: Send Welcome

API-->>App: Success
```

---

# 2. User Login

```mermaid
sequenceDiagram

actor User

participant App

participant Clerk

participant API

participant DB

User->>App: Login

App->>Clerk: Authenticate

Clerk-->>App: JWT

App->>API: GET /auth/me

API->>DB: Load Profile

DB-->>API: User

API-->>App: User Profile
```

---

# 3. Create Party

```mermaid
sequenceDiagram

actor Host

participant App

participant API

participant PartyService

participant DB

participant Queue

participant Reverb

Host->>App: Create Party

App->>API: POST /parties

API->>PartyService: Create

PartyService->>DB: Save Party

PartyService->>Queue: ScheduleReminders

PartyService->>Reverb: Broadcast

Reverb-->>Players: party.created

API-->>App: Party Created
```

---

# 4. Join Party

```mermaid
sequenceDiagram

actor Player

participant App

participant API

participant PartyService

participant DB

participant Reverb

Player->>App: Join

App->>API: POST /join

API->>PartyService: Join Party

PartyService->>DB: Add Player

PartyService->>Reverb: player.joined

Reverb-->>Party: Update Lobby

API-->>App: Success
```

---

# 5. Start Game

```mermaid
sequenceDiagram

actor Host

participant API

participant GameService

participant DB

participant Queue

participant Reverb

Host->>API: Start Game

API->>GameService: Initialize

GameService->>DB: Session

GameService->>Queue: AIHostJob

GameService->>Reverb: game.started

Reverb-->>Players: Game Begins
```

---

# 6. Complete Challenge

```mermaid
sequenceDiagram

actor Player

participant API

participant GameService

participant Wallet

participant Queue

participant Reverb

Player->>API: Complete Challenge

API->>GameService: Validate

GameService->>Wallet: Reward

Wallet->>Queue: CreditWalletJob

Queue->>Reverb: wallet.updated

Queue->>Reverb: score.updated

API-->>Player: Success
```

---

# 7. Wallet Purchase

```mermaid
sequenceDiagram

actor User

participant App

participant API

participant Payment

participant Wallet

participant DB

User->>App: Buy Tokens

App->>API: Initialize

API->>Payment: Create Payment

Payment-->>User: Payment Page

Payment->>API: Webhook

API->>Wallet: Credit

Wallet->>DB: Ledger Entry

API-->>App: Wallet Updated
```

---

# 8. Marketplace Purchase

```mermaid
sequenceDiagram

actor User

participant API

participant Marketplace

participant Wallet

participant DB

participant Inventory

participant Queue

User->>API: Purchase Pack

API->>Wallet: Debit (idempotency_key)

Wallet->>DB: Check Existing Transaction

DB-->>Wallet: Not Found

Wallet->>DB: Lock Wallet Row

Wallet->>DB: Debit + Ledger Entry

DB-->>Wallet: Committed

Wallet-->>Marketplace: Debit Confirmed

Marketplace->>Inventory: Grant Item

Marketplace->>Queue: CreatorRevenueJob

Marketplace->>Queue: AnalyticsJob

Marketplace-->>API: Purchase Complete

API-->>User: Purchased
```

---

# 9. Creator Payout

```mermaid
sequenceDiagram

participant Scheduler

participant RevenueService

participant DB

participant Payment

participant Creator

Scheduler->>RevenueService: Monthly Run

RevenueService->>DB: Calculate Revenue

RevenueService->>DB: Persist Payout Attempt (idempotency_key)

RevenueService->>Payment: Transfer (idempotency_key)

Payment-->>RevenueService: Transfer Accepted

RevenueService->>Payment: Reconcile Transfer Status (idempotency_key)

Payment-->>RevenueService: Transfer Confirmed

RevenueService->>DB: Mark Paid

RevenueService-->>Creator: Payout
```

---

# 10. Send Chat Message

```mermaid
sequenceDiagram

actor User

participant App

participant API

participant ChatService

participant DB

participant Reverb

User->>App: Send Message

App->>API: POST Message

API->>ChatService: Save

ChatService->>DB: Message

ChatService->>Reverb: message.created

Reverb-->>Party: Display Message
```

---

# 11. AI Party Host

```mermaid
sequenceDiagram

participant Queue

participant AI

participant DB

participant Reverb

Queue->>AI: Generate Response

AI-->>Queue: Response

Queue->>DB: Save

Queue->>Reverb: ai.host.generated

Reverb-->>Players: Display Host
```

---

# 12. Push Notification

```mermaid
sequenceDiagram

participant Event

participant Queue

participant Notification

participant Firebase

participant User

Event->>Queue: SendNotificationJob

Queue->>Notification: Build

Notification->>Firebase: Push

Firebase-->>User: Notification
```

---

# 13. Friend Request

```mermaid
sequenceDiagram

actor User

participant API

participant FriendService

participant DB

participant Reverb

User->>API: Add Friend

API->>FriendService: Create Request

FriendService->>DB: Save

FriendService->>Reverb: notification.created

API-->>User: Sent
```

---

# 14. Organization Invitation

```mermaid
sequenceDiagram

participant Admin

participant API

participant Organization

participant Email

participant User

Admin->>API: Invite Employee

API->>Organization: Create Invitation

Organization->>Email: Send Invite

Email-->>User: Invitation
```

---

# 15. Report Content

```mermaid
sequenceDiagram

actor User

participant API

participant Moderation

participant Queue

participant Admin

User->>API: Report

API->>Moderation: Save

Moderation->>Queue: Review

Queue-->>Admin: Notify
```

---

# 16. File Upload

```mermaid
sequenceDiagram

actor User

participant App

participant API

participant Storage

participant Queue

User->>App: Upload

App->>API: File

API->>Storage: Save

API->>Queue: OptimizeImageJob

Queue->>Storage: Thumbnail

API-->>App: Uploaded
```

---

# 17. Referral Reward

```mermaid
sequenceDiagram

participant Event

participant Queue

participant Wallet

participant Notification

Event->>Queue: ReferralRewardJob

Queue->>Wallet: Credit

Queue->>Notification: Notify
```

---

# 18. Leaderboard Update

```mermaid
sequenceDiagram

participant Game

participant Queue

participant Analytics

participant Reverb

Game->>Queue: LeaderboardJob

Queue->>Analytics: Aggregate

Queue->>Reverb: leaderboard.updated
```

---

# 19. Live Voice Room

```mermaid
sequenceDiagram

actor User

participant App

participant API

participant LiveKit

User->>App: Join Voice

App->>API: Request Token

API->>LiveKit: Generate Token

LiveKit-->>App: Token

App->>LiveKit: Connect

LiveKit-->>Users: Joined
```

---

# 20. Emergency Rollback

```mermaid
sequenceDiagram

participant Monitor

participant DevOps

participant Docker

participant Platform

Monitor->>DevOps: Alert

DevOps->>Docker: Rollback

Docker->>Platform: Previous Version

Platform-->>Users: Restored
```

---

# Cross-Service Interaction Rules

Controllers communicate with

Services

Services communicate with

Repositories

Repositories communicate with

Database

Queues communicate with

Workers

Workers communicate with

External Services

Realtime broadcasts originate

After successful persistence.

---

# Design Rules

✓ Controllers never communicate directly with the database.

✓ Services own business logic.

✓ Queues execute heavy operations.

✓ Events coordinate independent services.

✓ Reverb broadcasts only persisted state.

✓ LiveKit manages media only.

---

# Future Sequence Diagrams

```
Tournament Flow

Achievement Unlock

Guild Creation

Plugin Installation

Developer OAuth

AR Session

VR Party

AI Storytelling

Subscription Renewal

Corporate Training Session

Season Pass Progression
```

---

# Claude Code Instructions

When implementing workflows:

1. Follow these interaction sequences.
2. Never bypass the service layer.
3. Persist data before broadcasting.
4. Queue long-running operations.
5. Keep services loosely coupled through events.
6. Maintain transaction boundaries.
7. Update sequence diagrams whenever workflows change.
8. Keep Mermaid diagrams synchronized with implementation.

---

# Acceptance Criteria

The Sequence Diagram Reference is complete when:

- Every critical workflow is documented.
- Service interactions are standardized.
- Queue boundaries are defined.
- External integrations are visualized.
- Developers can implement features directly from these diagrams.
- Documentation remains synchronized with production behavior.

---
