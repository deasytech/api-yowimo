# Yowimo Database Entity Relationship Diagrams

**Version:** 1.0.0

**Status:** Database Visualization Specification

**Priority:** CRITICAL

**Owner:** Backend Platform Team

**Database**

PostgreSQL 16+

**Depends On**

- 03_DOMAIN_MODEL.md
- 04_DATABASE_ARCHITECTURE.md
- 38_DATABASE_SCHEMA_REFERENCE.md

---

# Purpose

This document visually represents every database relationship inside Yowimo.

It exists to help developers quickly understand

- Entity ownership
- Foreign keys
- One-to-One relationships
- One-to-Many relationships
- Many-to-Many relationships
- Aggregate boundaries
- Cross-domain interactions

---

# Diagram Standards

Notation

```
|| = One

o{ = Many

PK = Primary Key

FK = Foreign Key
```

---

# Domains

The database is divided into the following domains:

- Identity
- Social
- Party
- Game
- Wallet
- Marketplace
- Creator
- Enterprise
- AI
- Notifications
- Analytics
- Moderation
- Sponsors
- Infrastructure

---

# 1. Identity Domain

```mermaid
erDiagram

USERS ||--|| USER_PROFILES : owns

USERS ||--o{ DEVICES : registers

USERS ||--|| WALLETS : owns

USERS ||--o{ NOTIFICATIONS : receives

USERS ||--o{ FRIENDSHIPS : participates

USERS ||--o{ REPORTS : submits

USERS ||--o{ UPLOADS : uploads
```

---

# Identity Relationships

```
User

↓

Profile

↓

Wallet

↓

Devices

↓

Notifications

↓

Uploads
```

---

# 2. Friend System

```mermaid
erDiagram

USERS ||--o{ FRIENDSHIPS : sender

USERS ||--o{ FRIENDSHIPS : receiver

USERS ||--o{ BLOCKED_USERS : blocks
```

---

# 3. Party Domain

```mermaid
erDiagram

USERS ||--o{ PARTIES : hosts

PARTIES ||--o{ PARTY_PLAYERS : contains

USERS ||--o{ PARTY_PLAYERS : joins

PARTIES ||--o{ PARTY_INVITATIONS : sends

PARTIES ||--o{ PARTY_REACTIONS : receives

PARTIES ||--|| GAME_SESSIONS : creates
```

---

# Party Aggregate

```
Party

↓

Players

↓

Invitations

↓

Reactions

↓

Session

↓

Rounds

↓

Turns
```

---

# 4. Game Domain

```mermaid
erDiagram

GAMES ||--o{ GAME_MODES : supports

GAMES ||--o{ CARD_PACKS : contains

GAME_SESSIONS ||--o{ GAME_ROUNDS : has

GAME_ROUNDS ||--o{ TURNS : contains

USERS ||--o{ PLAYER_SCORES : owns
```

---

# Gameplay Flow

```
Game

↓

Card Pack

↓

Cards

↓

Session

↓

Round

↓

Turn

↓

Score
```

---

# 5. Card Content

```mermaid
erDiagram

CARD_PACKS ||--o{ CARDS : contains

CARDS ||--o{ CARD_VERSIONS : versions

CARDS }o--o{ CARD_TAGS : tagged
```

---

# Content Versioning

```
Pack

↓

Cards

↓

Versions

↓

Translations

↓

Tags
```

---

# 6. Wallet Domain

```mermaid
erDiagram

USERS ||--|| WALLETS : owns

WALLETS ||--o{ WALLET_TRANSACTIONS : records

WALLETS ||--o{ WALLET_REWARDS : receives

TOKEN_PACKAGES ||--o{ PAYMENT_TRANSACTIONS : purchased
```

---

# Wallet Aggregate

```
Wallet

↓

Transactions

↓

Rewards

↓

Purchases
```

---

# Ledger Flow

```
Wallet

↓

Transaction

↓

Balance

↓

Analytics
```

---

# 7. Marketplace

```mermaid
erDiagram

CREATORS ||--o{ MARKETPLACE_ITEMS : publishes

MARKETPLACE_ITEMS ||--o{ PURCHASE_ITEMS : purchased

PURCHASES ||--o{ PURCHASE_ITEMS : contains

USERS ||--o{ INVENTORY : owns

MARKETPLACE_ITEMS ||--o{ INVENTORY : grants

CREATORS ||--o{ CREATOR_REVENUE : earns
```

---

# Marketplace Aggregate

```
Creator

↓

Marketplace Item

↓

Purchase

↓

Inventory

↓

Revenue
```

---

# 8. Creator Domain

```mermaid
erDiagram

USERS ||--|| CREATORS : becomes

CREATORS ||--o{ CREATOR_FOLLOWERS : followed

CREATORS ||--o{ CREATOR_PAYOUTS : receives

CREATORS ||--|| CREATOR_STATISTICS : owns
```

---

# Creator Relationships

```
Creator

↓

Followers

↓

Marketplace

↓

Revenue

↓

Payouts

↓

Statistics
```

---

# 9. Enterprise Domain

```mermaid
erDiagram

ORGANIZATIONS ||--o{ WORKSPACES : owns

WORKSPACES ||--o{ DEPARTMENTS : contains

DEPARTMENTS ||--o{ EMPLOYEES : employs

EMPLOYEES ||--o{ ORGANIZATION_ROLES : assigned
```

---

# Enterprise Hierarchy

```
Organization

↓

Workspace

↓

Department

↓

Employee

↓

Role
```

---

# 10. Messaging Domain

```mermaid
erDiagram

CONVERSATIONS ||--o{ CONVERSATION_PARTICIPANTS : contains

USERS ||--o{ CONVERSATION_PARTICIPANTS : joins

CONVERSATIONS ||--o{ MESSAGES : stores

MESSAGES ||--o{ MESSAGE_REACTIONS : receives

MESSAGES ||--o{ MESSAGE_READS : tracked
```

---

# Messaging Flow

```
Conversation

↓

Participants

↓

Messages

↓

Reads

↓

Reactions
```

---

# 11. Notifications

```mermaid
erDiagram

USERS ||--o{ NOTIFICATIONS : receives

USERS ||--|| NOTIFICATION_PREFERENCES : configures
```

---

# 12. AI Domain

```mermaid
erDiagram

AI_PROMPTS ||--o{ AI_PROMPT_VERSIONS : versions

AI_PROMPTS ||--o{ AI_REQUESTS : generates

AI_REQUESTS ||--|| AI_RESPONSES : returns
```

---

# AI Pipeline

```
Prompt

↓

Version

↓

Request

↓

Response
```

---

# 13. Analytics Domain

```mermaid
erDiagram

USERS ||--o{ ANALYTICS_EVENTS : generates

PARTIES ||--o{ ANALYTICS_EVENTS : tracks

USERS ||--|| USER_METRICS : aggregates

DAILY_METRICS ||--o{ LEADERBOARD_SNAPSHOTS : generates
```

---

# Analytics Flow

```
User

↓

Events

↓

Aggregation

↓

Metrics

↓

Reports
```

---

# 14. Moderation Domain

```mermaid
erDiagram

USERS ||--o{ REPORTS : creates

REPORTS ||--o{ MODERATION_ACTIONS : results

USERS ||--o{ BANS : receives

BANS ||--o{ APPEALS : appeals
```

---

# Moderation Flow

```
Report

↓

Review

↓

Action

↓

Appeal
```

---

# 15. Sponsors

```mermaid
erDiagram

SPONSORS ||--o{ SPONSOR_CAMPAIGNS : creates

SPONSOR_CAMPAIGNS ||--o{ SPONSOR_REWARDS : issues

SPONSOR_CAMPAIGNS ||--o{ SPONSOR_IMPRESSIONS : records
```

---

# Sponsor Flow

```
Sponsor

↓

Campaign

↓

Reward

↓

Claim

↓

Analytics
```

---

# 16. Payments

```mermaid
erDiagram

PAYMENT_PROVIDERS ||--o{ PAYMENT_TRANSACTIONS : processes

PAYMENT_TRANSACTIONS ||--o{ PAYMENT_WEBHOOKS : confirms

PAYMENT_TRANSACTIONS ||--|| WALLET_TRANSACTIONS : credits
```

---

# Payment Flow

```
Provider

↓

Payment

↓

Webhook

↓

Wallet

↓

Ledger
```

---

# 17. Storage Domain

```mermaid
erDiagram

USERS ||--o{ UPLOADS : uploads

UPLOADS ||--|| MEDIA_ASSETS : generates
```

---

# 18. Audit Domain

```mermaid
erDiagram

USERS ||--o{ AUDIT_LOGS : performs
```

---

# Master Platform Diagram

```text
Users
 │
 ├── Profile
 ├── Wallet
 ├── Friends
 ├── Parties
 │      ├── Players
 │      ├── Rounds
 │      └── Turns
 │
 ├── Marketplace
 │      ├── Purchases
 │      ├── Inventory
 │      └── Creator Revenue
 │
 ├── Organizations
 │      ├── Workspaces
 │      ├── Departments
 │      └── Employees
 │
 ├── AI
 ├── Notifications
 ├── Analytics
 ├── Moderation
 └── Sponsors
```

---

# Aggregate Boundaries

Identity

```
User
```

Party

```
Party
```

Wallet

```
Wallet
```

Marketplace

```
Marketplace Item
```

Creator

```
Creator
```

Enterprise

```
Organization
```

---

# Cross-Domain Relationships

Examples

```
Party

↓

Wallet Reward

↓

Notification

↓

Analytics

↓

Leaderboard
```

---

# Cascade Rules

CASCADE

Party Players

Conversation Participants

Uploads

SET NULL

Analytics References

Historical Reports

RESTRICT

Wallet Transactions

Payment Transactions

Creator Payouts

Audit Logs

---

# Multi-Tenant Relationships

Every tenant-owned table includes

```
tenant_id
```

Cross-tenant relationships are forbidden.

---

# Diagram Maintenance Rules

Every new table

Must update

ER Diagram

Schema Reference

Migration Documentation

---

# Future Domains

Reserved

```
Guilds

Achievements

Badges

Quests

Season Pass

Tournaments

Developer Apps

Plugins

API Keys

Subscriptions

Digital Collectibles

AR Sessions

VR Rooms
```

---

# Claude Code Instructions

When introducing database changes:

1. Update the ER diagrams before merging.
2. Keep aggregates cohesive.
3. Avoid circular dependencies.
4. Respect aggregate boundaries.
5. Preserve referential integrity.
6. Document every new relationship.
7. Keep Mermaid diagrams synchronized with migrations.
8. Update this document whenever tables or relationships change.

---

# Acceptance Criteria

The ER Diagram Reference is complete when:

- Every domain has a visual diagram.
- Every relationship is documented.
- Aggregate boundaries are defined.
- Cross-domain interactions are clear.
- Cascade behaviors are documented.
- Developers can understand the data model without reading migrations.

---
