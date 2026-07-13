# Yowimo Data Flow Architecture

**Version:** 1.0.0

**Status:** Platform Data Flow Specification

**Priority:** CRITICAL

**Owner:** Platform Engineering

**Architecture**

React Native

React Query

Zustand

Laravel API

Services

Repositories

PostgreSQL

Redis

Laravel Reverb

LiveKit

Queues

**Depends On**

- 22_BACKEND_SERVICE_CATALOG.md
- 23_FRONTEND_ARCHITECTURE.md
- 39_REST_API_REFERENCE.md
- 40_WEBSOCKET_EVENT_CATALOG.md
- 41_DOMAIN_EVENT_CATALOG.md
- 42_QUEUE_JOB_REFERENCE.md
- 45_SEQUENCE_DIAGRAMS.md

---

# Purpose

This document explains how data moves throughout the Yowimo platform.

It documents

- Client Data Flow
- API Flow
- Domain Flow
- Database Flow
- Event Flow
- Queue Flow
- Realtime Flow
- Analytics Flow
- AI Flow

Developers should understand exactly where data comes from, where it goes, and why.

---

# Core Principle

Data should always have a single source of truth.

Never duplicate state unnecessarily.

---

# Platform Data Flow

```text
User

↓

React Native

↓

SDK

↓

REST API

↓

Controller

↓

Service

↓

Repository

↓

Database

↓

Domain Event

↓

Queue

↓

Realtime

↓

Client Update
```

---

# High-Level Architecture

```text
Presentation Layer

↓

Application Layer

↓

Domain Layer

↓

Persistence Layer

↓

Infrastructure Layer
```

---

# Presentation Layer

Responsible for

UI

Navigation

User Input

Animations

Accessibility

State Display

No business logic belongs here.

---

# Application Layer

Responsible for

Controllers

Validation

Authentication

Authorization

API Resources

This layer coordinates requests.

---

# Domain Layer

Responsible for

Business Rules

Services

Policies

Events

Value Objects

This is where platform logic lives.

---

# Persistence Layer

Responsible for

Repositories

Database

Redis

Storage

Search

No business decisions here.

---

# Infrastructure Layer

Responsible for

Queues

AI

Email

Storage

Payments

Maps

Push Notifications

Realtime

---

# User Action Flow

Example

Player presses

Ready

```text
Tap Button

↓

React Native

↓

Mutation

↓

REST API

↓

Controller

↓

Service

↓

Repository

↓

Database

↓

Domain Event

↓

Broadcast

↓

React Query Refresh

↓

UI Updated
```

---

# Frontend Data Flow

```text
Component

↓

React Query

↓

API SDK

↓

REST API

↓

Response

↓

Cache

↓

Component
```

---

# Local State Flow

Managed by

Zustand

Stores

Theme

Session

Navigation

Temporary UI

Bottom Sheets

Drafts

Never store server state here.

---

# Server State Flow

Managed by

React Query

Stores

Profile

Wallet

Friends

Marketplace

Notifications

Inventory

Organization

Creator Dashboard

---

# Cache Flow

```text
API Response

↓

React Query Cache

↓

Component

↓

Mutation

↓

Invalidate Cache

↓

Refetch
```

---

# Authentication Flow

```text
Clerk

↓

JWT

↓

API

↓

Middleware

↓

Controller

↓

Service
```

---

# Authorization Flow

```text
JWT

↓

Tenant

↓

Role

↓

Permission

↓

Policy

↓

Service
```

---

# API Flow

```text
Client

↓

Controller

↓

Form Request

↓

Service

↓

Repository

↓

Database
```

Controllers never access repositories directly.

---

# Service Flow

```text
Validation

↓

Business Rules

↓

Repository

↓

Events

↓

Queue

↓

Response
```

Services never return database models directly.

---

# Repository Flow

```text
Service

↓

Repository

↓

Query Builder

↓

Database
```

Repositories never call external services.

---

# Database Flow

```text
Repository

↓

Transaction

↓

Commit

↓

Domain Event
```

Events fire only after successful commits.

---

# Queue Flow

```text
Event

↓

Queue

↓

Worker

↓

External Service

↓

Database

↓

Broadcast
```

---

# Notification Flow

```text
Domain Event

↓

Notification Job

↓

Email

↓

Push

↓

Realtime

↓

Database
```

---

# Wallet Flow

```text
Purchase

↓

Payment

↓

Webhook

↓

Wallet Service

↓

Ledger

↓

Broadcast

↓

Analytics
```

Wallet balance is always derived from the ledger.

---

# Marketplace Flow

```text
Purchase

↓

Wallet Validation

↓

Inventory

↓

Creator Revenue

↓

Analytics

↓

Notification
```

---

# Creator Flow

```text
Creator

↓

Submission

↓

Moderation

↓

Approval

↓

Marketplace

↓

Sales

↓

Revenue

↓

Payout
```

---

# Corporate Flow

```text
Organization

↓

Workspace

↓

Department

↓

Event

↓

Employees

↓

Analytics
```

---

# Chat Flow

```text
Message

↓

Database

↓

Moderation

↓

Broadcast

↓

Recipients
```

Never broadcast before persistence.

---

# Voice Flow

```text
Join Voice

↓

API

↓

LiveKit Token

↓

LiveKit

↓

Participants
```

Voice media never passes through Laravel.

---

# AI Flow

```text
Request

↓

AI Orchestrator

↓

Prompt Library

↓

Provider

↓

Moderation

↓

Database

↓

Broadcast
```

All AI interactions go through the AI Orchestrator.

---

# Analytics Flow

```text
Client Event

↓

Analytics API

↓

Queue

↓

Aggregation

↓

Warehouse

↓

Dashboard
```

Analytics never block user requests.

---

# Payment Flow

```text
Client

↓

Payment Gateway

↓

Webhook

↓

Payment Service

↓

Wallet

↓

Ledger

↓

Receipt
```

Webhooks are the source of truth for payment completion.

---

# Referral Flow

```text
Invite

↓

Registration

↓

Verification

↓

Reward

↓

Wallet

↓

Notification
```

---

# Sponsor Flow

```text
Campaign

↓

Player

↓

Completion

↓

Reward

↓

Wallet

↓

Analytics
```

---

# Search Flow

```text
Search Query

↓

Search Service

↓

Database

↓

Results

↓

Cache
```

Future

ElasticSearch

OpenSearch

---

# Upload Flow

```text
Client

↓

Storage

↓

Virus Scan

↓

Optimization

↓

Metadata

↓

CDN
```

---

# Error Flow

```text
Failure

↓

Exception

↓

Logging

↓

Monitoring

↓

Recovery

↓

User Response
```

Never expose internal exceptions.

---

# Realtime Flow

```text
Database Commit

↓

Domain Event

↓

Broadcast Event

↓

Reverb

↓

Clients

↓

React Query Cache Update
```

---

# Offline Flow

```text
Action

↓

Offline Queue

↓

Reconnect

↓

Sync

↓

Confirmation
```

---

# Synchronization Flow

Priority

```
Wallet

↓

Messages

↓

Marketplace

↓

Friends

↓

Notifications

↓

Analytics
```

---

# Data Ownership

Client owns

Temporary UI

Backend owns

Business Data

Database owns

Persistent State

Redis owns

Cache

---

# Event Flow

```text
Business Event

↓

Listeners

↓

Jobs

↓

Notifications

↓

Realtime

↓

Analytics
```

---

# Logging Flow

```text
Application

↓

Structured Logs

↓

Aggregation

↓

Dashboard

↓

Alert
```

---

# Security Flow

```text
Request

↓

Authentication

↓

Authorization

↓

Validation

↓

Rate Limiting

↓

Business Logic

↓

Response
```

---

# Data Retention

Temporary Cache

Minutes

Notifications

90 Days

Logs

90 Days

Analytics

2 Years

Financial Data

7 Years

Audit Logs

7 Years

---

# Data Integrity Rules

✓ Never trust client data.

✓ Validate every request.

✓ Persist before broadcasting.

✓ Ledger is immutable.

✓ Audit sensitive actions.

✓ Events are immutable.

✓ Services own business rules.

---

# Future Data Flows

```
Plugin System

Developer API

Guilds

Achievements

Season Pass

AR Sessions

VR Rooms

Cross-Platform Sync

Offline AI

Federated Search
```

---

# Claude Code Instructions

When implementing data flows:

1. Follow the layer boundaries.
2. Never bypass services.
3. Keep a single source of truth.
4. Persist data before broadcasting.
5. Use queues for slow operations.
6. Separate server state from local state.
7. Keep caches synchronized.
8. Update this document whenever a new flow is introduced.

---

# Acceptance Criteria

The Data Flow Architecture is complete when:

- Every major feature has a documented data flow.
- Layer responsibilities are clearly defined.
- State ownership is unambiguous.
- Realtime updates follow persistence.
- AI, payments, and analytics follow standardized pipelines.
- Developers can trace data from user interaction to persistence and back to the UI.

---
