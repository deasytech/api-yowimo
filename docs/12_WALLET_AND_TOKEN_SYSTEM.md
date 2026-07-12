# Yowimo Wallet & Token Economy

**Version:** 1.0.0

**Status:** Core Business Specification

**Priority:** CRITICAL

**Owner:** Platform Finance Engineering

**Depends On**

- 03_DOMAIN_MODEL.md
- 04_DATABASE_ARCHITECTURE.md
- 05_API_STANDARDS.md
- 06_SECURITY_STANDARDS.md
- 07_EVENT_CATALOG.md
- 10_QUEUE_AND_BACKGROUND_JOBS.md

---

# Purpose

The Wallet is the financial backbone of Yowimo.

Every token earned, spent, purchased, sponsored, refunded, or rewarded passes through the Wallet System.

The Wallet must be:

- Accurate
- Auditable
- Fraud-resistant
- Immutable
- Scalable

No other service is allowed to modify token balances directly.

---

# Design Philosophy

The wallet does NOT store money.

The wallet stores **tokens**.

Tokens are the platform currency used to:

- Join premium parties
- Buy card packs
- Purchase cosmetics
- Unlock premium AI voices
- Unlock themes
- Participate in sponsored events
- Redeem future rewards

Every token movement must be traceable forever.

---

# Core Principles

✓ Ledger Driven

✓ Immutable

✓ Auditable

✓ Atomic

✓ Server Authoritative

✓ Idempotent

---

# Wallet Architecture

```text
User

↓

Wallet

↓

Ledger

↓

Transactions

↓

Rewards

↓

Purchases
```

---

# Wallet Components

Every user owns exactly one wallet.

Wallet contains

```
Current Balance

↓

Ledger Entries

↓

Transactions

↓

Snapshots

↓

Statistics
```

---

# Wallet Entity

Fields

```
id

user_id

balance

lifetime_earned

lifetime_spent

created_at

updated_at
```

**Note**

`balance` is a cached value.

The source of truth is the ledger.

---

# Ledger

The Ledger is append-only.

Nothing inside the ledger is ever updated.

Nothing is deleted.

Example

```
+50

Reward

↓

-20

Marketplace

↓

+100

Referral

↓

-10

Party Entry
```

Balance is derived from these entries.

---

# Ledger Entry

Fields

```
id

wallet_id

transaction_id

type

amount

balance_after

reason

metadata

created_at
```

---

# Ledger Types

Credit

Debit

Reserve

Release

Adjustment (Admin Only)

Refund

---

# Transactions

Transactions represent financial operations.

Examples

Purchase

Reward

Sponsor Credit

Referral Bonus

Marketplace Purchase

Ad Reward

Refund

Party Entry

---

# Transaction Lifecycle

```mermaid
stateDiagram-v2

Pending

-->Processing

Processing

-->Completed

Processing

-->Failed

Completed

-->Refunded
```

---

# Token Sources

Players may earn tokens from:

Signup Bonus

Referral Bonus

Watching Ads

Winning Games

Daily Login

Weekly Streak

Achievements

Sponsors

Marketplace Promotions

Admin Rewards

Season Pass (Future)

Creator Rewards (Future)

---

# Token Sinks

Players may spend tokens on:

Party Entry

Premium Card Packs

Themes

Voice Packs

Animations

Premium AI

Marketplace

Tournament Entry

Future Cosmetics

---

# Signup Bonus

Default

```
10 Tokens
```

Granted once.

---

# Referral Bonus

Inviter

```
+50 Tokens
```

Invitee

```
+25 Tokens
```

Granted after:

Verified Account

↓

Completed First Party

Prevents fake accounts.

---

# Advertisement Rewards

Supported Providers

Google AdMob

Unity Ads (Future)

Rewarded ads only.

Flow

```text
Watch Ad

↓

Provider Verification

↓

Backend Verification

↓

Credit Wallet
```

Never trust the client.

---

# Party Rewards

Examples

Participation

```
+10
```

Winner

```
+50
```

MVP

```
+75
```

Funniest

```
+20
```

Most Daring

```
+20
```

---

# Achievement Rewards

Examples

First Party

```
+25
```

100 Parties

```
+200
```

Invite 10 Friends

```
+150
```

No Skip Game

```
+50
```

---

# Sponsor Rewards

Sponsors may:

Pay Entry Fees

Reward Winners

Fund Tournaments

Unlock Premium Packs

Sponsor rewards are recorded separately for analytics.

---

# Marketplace Purchases

Purchase Flow

```text
Purchase

↓

Validate Balance

↓

Reserve Tokens

↓

Grant Product

↓

Debit Wallet

↓

Ledger Entry

↓

Complete
```

---

# Reservation System

Expensive operations reserve tokens first.

Example

```
Balance

500

↓

Reserve

200

↓

Available

300
```

If purchase fails

↓

Release Reservation

---

# Refund Policy

Refunds create a new ledger entry.

Never modify previous entries.

Example

```
Purchase

-200

↓

Refund

+200
```

---

# Wallet Snapshots

Nightly snapshots store

Balance

Lifetime Earned

Lifetime Spent

Transaction Count

Used for reporting only.

---

# Fraud Detection

Detect

Repeated Rewards

Impossible Earnings

Rapid Purchases

Duplicate Ads

Multiple Devices

Referral Abuse

Suspicious Activity triggers review.

---

# Spending Rules

Cannot spend

Negative Balance

Reserved Tokens

Locked Rewards

Future Expiring Tokens

---

# Token Expiration

Current Policy

```
No Expiration
```

Future

Promotional tokens may expire.

Expiration must create ledger entries.

---

# Sponsor Credits

Sponsor balances are isolated.

Sponsors cannot withdraw user balances.

Sponsor Wallets are independent from Player Wallets.

---

# Admin Adjustments

Admins may issue adjustments.

Every adjustment requires

Reason

Approval

Audit Log

Admin ID

---

# Multi-Currency Future

Current

```
Tokens
```

Future

```
Tokens

XP

Gems

Creator Credits

Corporate Credits
```

Wallet architecture already supports multiple asset types.

---

# Financial Integrity

Every operation must use database transactions.

Example

```php
DB::transaction(function () {

    Reserve Tokens

    Create Transaction

    Create Ledger Entry

    Update Cached Balance

});
```

---

# Idempotency

Wallet operations must never execute twice.

Duplicate requests return the original transaction.

---

# Security

Only WalletService may modify balances.

Never

```php
$wallet->balance += 100;
```

Always

```php
WalletService::credit(...)
```

---

# Events

```
wallet.created

wallet.credited

wallet.debited

wallet.reserved

wallet.released

transaction.completed

reward.granted

purchase.completed

refund.completed
```

---

# Analytics

Track

Average Balance

Daily Spending

Revenue

Reward Distribution

Marketplace Conversion

Sponsor Activity

Ad Revenue

Referral ROI

---

# Monitoring

Alert on

Negative Balances

Duplicate Transactions

Ledger Mismatch

Failed Purchases

Unusual Reward Spikes

Sponsor Abuse

---

# Future Features

Subscriptions

Battle Pass

Gift Tokens

Player-to-Player Gifts

Creator Marketplace

NFT Assets (Optional)

Cross-Game Wallet

Regional Pricing

---

# Claude Code Instructions

When implementing wallet functionality:

1. Never modify balances directly.
2. Always create a transaction.
3. Always create a ledger entry.
4. Use database transactions.
5. Emit financial events.
6. Log audit records.
7. Ensure idempotency.
8. Validate permissions before every operation.
9. Update this document when introducing new token sources or spending mechanisms.

---

# Acceptance Criteria

The Wallet System is complete when:

- Every token movement is auditable.
- Ledger is immutable.
- Balances are derived consistently.
- Fraud detection hooks exist.
- Purchases and rewards are transactional.
- Sponsor funds remain isolated.
- Future currencies can be added without redesign.

---
