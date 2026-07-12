# Yowimo Architecture Decision Records (ADR)

**Version:** 1.0.0

**Status:** Living Architecture Documentation

**Priority:** CRITICAL

**Owner:** CTO / Engineering Leadership

---

# Purpose

Architecture Decision Records (ADR) document **why** important technical decisions were made.

Code explains _how_.

Architecture explains _what_.

ADRs explain _why_.

Every major architectural decision should have an ADR.

---

# Philosophy

Future engineers should never ask

> "Why was this built this way?"

The answer should already exist here.

---

# ADR Lifecycle

```text
Proposal

↓

Discussion

↓

Approved

↓

Implemented

↓

Reviewed

↓

Deprecated (if necessary)
```

---

# ADR Template

Every decision contains

```
ADR Number

Title

Status

Date

Owner

Context

Decision

Alternatives

Consequences

Future Review
```

---

# ADR-001

## Laravel 12 as Backend Framework

### Status

Approved

### Context

Yowimo requires

High developer productivity

Strong ecosystem

Queue system

Broadcasting

Authentication

Excellent community support

### Decision

Laravel 12 selected.

### Alternatives

NestJS

Spring Boot

ASP.NET

Django

Phoenix

### Why Laravel?

Excellent ecosystem

Laravel Reverb

Laravel Horizon

Queues

Events

Rapid development

Large talent pool

### Review

Every 2 years.

---

# ADR-002

## PostgreSQL as Primary Database

### Status

Approved

### Context

Yowimo stores

Wallets

Marketplace

Analytics

Realtime metadata

Enterprise data

### Decision

PostgreSQL selected.

### Alternatives

MySQL

MariaDB

MongoDB

CockroachDB

### Why?

Excellent relational integrity

JSON support

Performance

Scalability

Extensions

Future partitioning

---

# ADR-003

## Redis

### Decision

Redis handles

Queues

Caching

Sessions

Broadcasting

Rate Limiting

Presence

Locks

### Alternatives

RabbitMQ

Memcached

Kafka

---

# ADR-004

## LiveKit

### Decision

Use LiveKit for

Voice

Video

Recording

Future Screen Sharing

### Alternatives

Agora

Twilio

Daily

WebRTC Custom

### Why?

Open Source

Self-hostable

Excellent SDK

Scalable

---

# ADR-005

## Laravel Reverb

### Decision

Realtime layer

### Responsibilities

Presence

Chat

Game Events

Countdowns

Leaderboard Updates

### Alternatives

Pusher

Ably

Socket.IO

Firebase

---

# ADR-006

## Clerk Authentication

### Decision

Authentication delegated to Clerk.

### Why?

Security

MFA

Social Login

Device Management

Passwordless

Organization Support

---

# ADR-007

## React Native + Expo

### Decision

Mobile applications built using

Expo

React Native

### Alternatives

Flutter

Native

Ionic

### Why?

Developer productivity

OTA updates

Strong ecosystem

Cross-platform

---

# ADR-008

## NativeWind

### Decision

NativeWind is the styling solution.

### Alternatives

Styled Components

StyleSheet

Tamagui

### Why?

Consistency

Utility-first

Fast development

---

# ADR-009

## React Query

### Decision

React Query manages server state.

### Alternatives

Redux Toolkit Query

Apollo

SWR

### Why?

Caching

Retries

Synchronization

Offline support

---

# ADR-010

## Zustand

### Decision

Global application state.

### Alternatives

Redux

MobX

Context API

### Why?

Minimal API

Performance

Simplicity

---

# ADR-011

## Event-Driven Architecture

### Decision

Business domains communicate through events.

### Examples

Wallet Credited

↓

Reward Granted

↓

Notification

↓

Analytics

### Benefits

Loose coupling

Scalability

Extensibility

---

# ADR-012

## Service Layer

Business logic belongs inside Services.

Never inside

Controllers

Models

Views

Components

---

# ADR-013

## Repository Pattern

Repositories abstract persistence.

Allows

Testing

Future storage changes

Cleaner architecture

---

# ADR-014

## Multi-Tenant Design

Application-level tenant isolation.

Future

Dedicated databases.

Reason

Lower operational cost

Enterprise readiness

---

# ADR-015

## Wallet Ledger

Ledger is immutable.

Balances are derived.

Never edit balances directly.

Reason

Financial integrity

Auditability

Fraud prevention

---

# ADR-016

## AI Orchestrator

All AI requests pass through

AIOrchestratorService

Reason

Provider abstraction

Prompt versioning

Fallback

Monitoring

Cost control

---

# ADR-017

## Marketplace

Marketplace is a first-class platform feature.

Reason

Creator economy

Revenue diversification

Long-term engagement

---

# ADR-018

## Creator Economy

Creators retain ownership.

Yowimo distributes.

Revenue sharing built into platform.

---

# ADR-019

## Corporate Platform

Enterprise features remain inside the same platform.

Avoid maintaining separate codebases.

Reason

Shared innovation

Lower maintenance

Unified product

---

# ADR-020

## AI Prompt Library

Prompts are versioned assets.

Never inline prompts.

Reason

Testing

Maintainability

Rollback

---

# ADR-021

## Content Versioning

Published content cannot be overwritten.

Every modification creates a new version.

---

# ADR-022

## Queue-First Philosophy

Heavy work executes asynchronously.

Examples

AI

Video

Emails

Analytics

Marketplace Processing

---

# ADR-023

## S3 Storage

Media stored in S3.

Never local filesystem.

Reason

Scalability

Reliability

CDN Integration

---

# ADR-024

## API Versioning

Public APIs are versioned.

```
/api/v1
```

Breaking changes require

```
v2
```

---

# ADR-025

## Feature Flags

Major features released behind feature flags.

Reason

Gradual rollout

Rollback

Testing

---

# ADR-026

## Docker Everywhere

Every service runs inside Docker.

Reason

Consistency

Deployment

Scalability

---

# ADR-027

## Infrastructure as Code

Infrastructure managed as code.

Reason

Repeatability

Recovery

Automation

---

# ADR-028

## Observability First

Every service emits

Logs

Metrics

Tracing

Health Checks

Reason

Operational excellence

---

# ADR-029

## API SDK

All clients consume

Shared SDK

Reason

Consistency

Type safety

Lower maintenance

---

# ADR-030

## Localization First

Localization is built into architecture.

Not added later.

Reason

Global platform vision.

---

# ADR Process

New architecture changes require

Proposal

↓

Discussion

↓

Approval

↓

ADR

↓

Implementation

---

# Review Schedule

Review ADRs

Every

6 Months

Major Releases

Major Infrastructure Changes

---

# Deprecation

Deprecated ADRs remain documented.

History is preserved.

Never delete ADRs.

---

# Future ADRs

Examples

Microservices

GraphQL

Edge Computing

Dedicated AI Cluster

Kubernetes

Global CDN

Offline AI

AR Features

VR Support

---

# Documentation

Each ADR links to

Architecture

Implementation

Relevant Services

Pull Requests

Design Documents

---

# Claude Code Instructions

When making architectural changes:

1. Check existing ADRs.
2. Do not violate approved decisions without creating a new ADR.
3. Document significant architectural changes.
4. Keep ADR history immutable.
5. Reference ADRs in implementation PRs.
6. Update this document when new platform decisions are approved.

---

# Acceptance Criteria

The ADR system is complete when:

- Every major technical decision has an ADR.
- Architectural reasoning is preserved.
- New engineers understand why technologies were chosen.
- Future architectural changes are documented.
- Historical decisions remain traceable.

---
