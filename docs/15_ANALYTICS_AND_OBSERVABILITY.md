# Yowimo Analytics & Observability

**Version:** 1.0.0

**Status:** Core Platform Specification

**Priority:** CRITICAL

**Owner:** Platform Engineering + Product Analytics

**Depends On**

- 05_API_STANDARDS.md
- 07_EVENT_CATALOG.md
- 08_GAME_ENGINE.md
- 09_REALTIME_ARCHITECTURE.md
- 10_QUEUE_AND_BACKGROUND_JOBS.md
- 12_WALLET_AND_TOKEN_SYSTEM.md
- 13_MARKETPLACE_ARCHITECTURE.md
- 14_NOTIFICATION_SYSTEM.md

---

# Purpose

Everything inside Yowimo should be measurable.

If something cannot be measured, it cannot be improved.

Analytics answers:

- What are players doing?
- Where are users dropping off?
- Which games are most engaging?
- Which card packs generate revenue?
- Which notifications work?
- Why are users leaving?

Observability answers:

- Is the system healthy?
- Are queues failing?
- Are APIs slow?
- Is Reverb online?
- Is LiveKit healthy?

---

# Philosophy

Separate:

Business Analytics

from

System Observability.

Never mix infrastructure metrics with product metrics.

---

# Analytics Architecture

```text
Application

↓

Domain Events

↓

Analytics Pipeline

↓

Warehouse

↓

Dashboards

↓

Business Intelligence
```

---

# Sources

Backend

Mobile App

Web

Admin Panel

AI Host

Marketplace

Wallet

Notifications

LiveKit

Reverb

---

# Event Pipeline

```text
Application Event

↓

Analytics Event

↓

Queue

↓

Storage

↓

Dashboard
```

Analytics must never slow gameplay.

---

# Event Naming

Always

```
entity.action
```

Examples

```
party.created

game.started

card.played

wallet.credited

purchase.completed

notification.opened
```

---

# Event Payload

Example

```json
{
    "event": "game.started",

    "user_id": "...",

    "party_id": "...",

    "timestamp": "..."
}
```

---

# Product Analytics

Track

New Users

Active Users

Returning Users

Session Length

Parties Created

Parties Joined

Games Played

Cards Played

Challenge Completion

Retention

Conversion

---

# Gameplay Analytics

Track

Most Played Game

Average Game Length

Average Round Length

Average Turn Time

Skipped Cards

Skipped Challenges

Votes Cast

Audience Size

Reactions

Disconnect Rate

---

# Party Analytics

Track

Party Size

Public vs Private

Hybrid Usage

Corporate Usage

Average Players

Host Retention

Party Completion

---

# Wallet Analytics

Track

Tokens Earned

Tokens Spent

Average Balance

Top Earners

Marketplace Spend

Reward Distribution

Sponsor Spend

Ad Rewards

---

# Marketplace Analytics

Track

Product Views

Purchases

Revenue

Conversion Rate

Refunds

Popular Categories

Trending Packs

Search Queries

Wishlist Growth (Future)

---

# AI Analytics

Track

Requests

Latency

Cost

Prompt Tokens

Completion Tokens

Voice Usage

Translations

Moderation Events

Fallback Rate

---

# Notification Analytics

Track

Delivered

Opened

Clicked

Dismissed

Failed

Unsubscribed

Conversion

---

# Referral Analytics

Track

Invites Sent

Accepted

Completed

Rewarded

Fraud Attempts

---

# Advertisement Analytics

Track

Ads Viewed

Completion Rate

Reward Success

Revenue

Fill Rate

CTR

---

# User Funnel

Track

Install

↓

Register

↓

Verify

↓

Create Profile

↓

Create Party

↓

Invite Friends

↓

Play Game

↓

Purchase

↓

Return

Measure drop-off between every step.

---

# Retention

Calculate

D1

D7

D14

D30

D90

Monthly Active Users

Weekly Active Users

Daily Active Users

---

# Cohort Analysis

Group users by

Registration Date

Country

Platform

Referral Source

Marketing Campaign

Device

App Version

---

# Revenue Analytics

Track

Daily Revenue

Monthly Revenue

Average Revenue Per User

Lifetime Value

Sponsor Revenue

Marketplace Revenue

Advertisement Revenue

Corporate Revenue

---

# Leaderboard Analytics

Track

Top Players

Top Hosts

Most Active Cities

Most Played Packs

Most Active Communities

---

# Community Analytics

Track

Friend Growth

Messages

Voice Minutes

Video Minutes

Comments

Reactions

Shares

Highlights Viewed

---

# Geographic Analytics

Track

Country

State

City

Timezone

Language

Region

Useful for localization.

---

# Device Analytics

Track

Operating System

App Version

Device Type

Screen Size

Performance Class

Network Type

---

# Performance Metrics

Track

API Latency

Database Time

Queue Time

Cache Hit Rate

Broadcast Latency

LiveKit Latency

Storage Usage

Memory

CPU

---

# Logging

Every request logs

Correlation ID

Route

User

Duration

Status Code

IP

Device

---

# Structured Logs

Never

```
Something happened
```

Always

```json
{
    "event": "wallet.credit",

    "user": "...",

    "amount": 100
}
```

---

# Distributed Tracing

Every request carries

```
X-Correlation-ID
```

Across

API

Queues

AI

Notifications

Broadcasts

---

# Error Monitoring

Track

Exceptions

Validation Errors

Timeouts

Database Failures

Queue Failures

Broadcast Failures

AI Errors

Payment Errors

---

# Dashboards

Engineering Dashboard

Product Dashboard

Finance Dashboard

AI Dashboard

Support Dashboard

Executive Dashboard

---

# Executive KPIs

Monthly Active Users

Daily Active Users

Revenue

Retention

Marketplace Sales

Average Party Size

Average Session

Net Revenue

Sponsor Revenue

Growth Rate

---

# Alerts

Notify engineering if

API > 1 sec

Queue Delay > 60 sec

Database Down

Redis Down

LiveKit Down

Reverb Down

High Error Rate

Failed Payments

---

# Health Checks

Expose

```
/health
```

Checks

Database

Redis

Queue

Storage

Broadcast

LiveKit

AI Providers

---

# Data Retention

Application Logs

90 Days

Analytics Events

2 Years

Financial Records

Indefinite

Audit Logs

Indefinite

---

# Privacy

Never store

Passwords

Tokens

Secrets

Payment Data

Private Messages

Sensitive Personal Data

inside analytics events.

---

# Future Features

Predictive Analytics

AI Churn Prediction

Recommendation Engine

Heat Maps

A/B Testing

Experiment Framework

Fraud Detection Models

Real-Time Executive Dashboard

---

# Claude Code Instructions

When implementing analytics:

1. Emit analytics from domain events.
2. Never block requests waiting for analytics.
3. Queue analytics processing.
4. Use structured logs.
5. Carry correlation IDs across services.
6. Separate business metrics from infrastructure metrics.
7. Protect user privacy.
8. Update this document whenever new KPIs or dashboards are introduced.

---

# Acceptance Criteria

The Analytics & Observability platform is complete when:

- Every major user action emits analytics.
- Infrastructure health is measurable.
- Product teams can answer engagement questions.
- Finance can track revenue accurately.
- Engineering can trace failures end-to-end.
- Executives have real-time business dashboards.
- Analytics never impacts gameplay performance.

---
