# Yowimo Backend Implementation Guide

**Version:** 1.0.0

**Status:** Backend Engineering Standards

**Priority:** CRITICAL

**Owner:** Backend Platform Team

**Framework**

Laravel 12

PHP 8.4+

PostgreSQL

Redis

Laravel Reverb

LiveKit

Clerk

**Architecture**

DDD Inspired

Service Layer

Repository Pattern

Event Driven

Queue First

API First

---

# Purpose

This guide defines how backend code is written across Yowimo.

Every engineer and AI coding assistant must follow these standards.

Consistency is more valuable than individual coding preferences.

---

# Engineering Principles

Every implementation must be

Readable

Maintainable

Testable

Scalable

Observable

Secure

Reusable

---

# Project Structure

```
app/

├── Actions/
├── Console/
├── DTOs/
├── Enums/
├── Events/
├── Exceptions/
├── Helpers/
├── Http/
│   ├── Controllers/
│   ├── Middleware/
│   ├── Requests/
│   └── Resources/
├── Jobs/
├── Listeners/
├── Models/
├── Notifications/
├── Observers/
├── Policies/
├── Providers/
├── Repositories/
├── Rules/
├── Services/
├── Traits/
├── ValueObjects/
└── Support/
```

---

# Module Organization

Every business domain owns its code.

Example

```
Services/

Party/

Wallet/

Marketplace/

AI/

Organization/

Creator/

Notification/
```

Avoid giant shared service folders.

---

# Controllers

Controllers coordinate requests.

Controllers never contain business logic.

Good

```php
return $this->partyService->create($request->validated());
```

Bad

```php
// 300 lines of logic
```

Maximum

150 lines

Preferred

<80 lines

---

# Form Requests

Every endpoint uses

Laravel Form Requests

Validation belongs here.

Never

```php
$request->validate(...)
```

inside controllers.

---

# Services

Services own business rules.

Responsibilities

Validation

Transactions

Policies

Events

Queues

Repositories

Never

Return Views

Access Request objects

Generate HTTP responses

---

# Repository Pattern

Repositories own persistence.

Example

```
PartyRepository

WalletRepository

CreatorRepository

MarketplaceRepository
```

Repositories never

Call APIs

Broadcast events

Dispatch jobs

---

# Models

Models represent data.

Models should remain thin.

Allowed

Relationships

Scopes

Accessors

Mutators

Casts

Forbidden

Business workflows

HTTP logic

AI logic

Payments

---

# DTOs

Every complex service receives DTOs.

Example

```
CreatePartyData

PurchasePackData

RewardPlayerData

InviteEmployeeData
```

Never pass raw request arrays through services.

---

# Enums

Replace magic strings.

Example

```php
PartyStatus::LIVE

WalletTransactionType::PURCHASE

UserRole::CREATOR
```

Never

```php
"live"

"creator"
```

inside services.

---

# Value Objects

Examples

Money

Coordinates

EmailAddress

PhoneNumber

Currency

Language

Theme

Timezone

Immutable.

---

# Events

Fire events

After successful transactions.

Example

```
PartyStarted

RewardGranted

WalletCredited

PurchaseCompleted
```

---

# Listeners

Listeners coordinate secondary work.

Examples

Notifications

Analytics

Realtime

Email

Rewards

Never modify core business logic.

---

# Jobs

Heavy work belongs in queues.

Examples

AI

Email

Video

Analytics

Reports

Media

Notifications

Never block HTTP requests.

---

# Notifications

Use Laravel Notifications.

Channels

Database

Push

Email

SMS (Future)

---

# Policies

Every protected model has

Laravel Policy

Examples

PartyPolicy

WalletPolicy

CreatorPolicy

OrganizationPolicy

---

# Middleware

Keep focused.

Examples

Authenticate

Tenant

Admin

Throttle

Verified

Locale

Never perform business logic.

---

# API Resources

Always return

API Resources

Never

```php
return Model::all();
```

Example

```
PartyResource

WalletResource

CreatorResource
```

---

# Exception Handling

Use custom exceptions.

Examples

```
InsufficientBalanceException

PartyFullException

CreatorNotVerifiedException
```

Never throw generic

```
Exception
```

---

# Database Transactions

Use transactions for

Wallet

Payments

Marketplace

Creator Revenue

Organization Imports

Example

```php
DB::transaction(function () {

});
```

---

# Service Example

```php
CreatePartyService

↓

Validate

↓

Repository

↓

Event

↓

Queue

↓

Resource
```

---

# Dependency Injection

Always constructor injection.

Never

```php
new WalletService()
```

inside code.

---

# Configuration

Never hardcode.

Always

```php
config(...)
```

or

Environment Variables.

---

# Logging

Use structured logs.

Example

```php
Log::info('Party created', [

'party_id' => $party->id,

'user_id' => $user->id

]);
```

Never

```php
Log::info($request);
```

---

# Caching

Cache only

Expensive reads.

Never cache

Wallet Balance

Authentication

Permissions

---

# Naming Standards

Services

```
CreatePartyService
```

Repositories

```
PartyRepository
```

Requests

```
CreatePartyRequest
```

Resources

```
PartyResource
```

Policies

```
PartyPolicy
```

Events

```
PartyStarted
```

Jobs

```
GenerateHighlightsJob
```

---

# API Standards

REST

Versioned

```
/api/v1
```

Standard Responses

Use API Resources.

Never expose

Internal IDs

Secrets

Stack traces

---

# Queue Standards

Dispatch

After commit.

Always

Queue

AI

Emails

Analytics

Image Processing

---

# AI Integration

All AI calls go through

```
AIOrchestratorService
```

Never call providers directly.

---

# Payment Integration

Only

PaymentService

communicates with

Paystack

Stripe

Future providers.

---

# Realtime

Only

Broadcast Events

communicate with

Laravel Reverb.

Never broadcast directly from controllers.

---

# LiveKit

Only

VoiceService

RoomService

TokenService

interact with LiveKit.

---

# Security

Always

Authorize

Validate

Audit

Never trust

Client input.

---

# Feature Flags

Wrap experimental features.

Example

```php
Feature::enabled('guilds')
```

---

# Testing

Every feature requires

Unit Tests

Feature Tests

Policy Tests

Queue Tests

Event Tests

---

# Code Style

Laravel Pint

PSR-12

Strict Types

Readonly where applicable.

---

# Static Analysis

Required

PHPStan Level Max

Larastan

Dead Code Detection

---

# Documentation

Every Service

Must contain

Purpose

Inputs

Outputs

Exceptions

Events

Jobs

Dependencies

---

# Performance

Avoid

N+1 Queries

SELECT \*

Repeated Queries

Blocking IO

Use

Eager Loading

Chunking

Lazy Collections

Queues

---

# Database Rules

Never

Update Wallet Balance Directly

Delete Ledger Entries

Bypass Transactions

Ignore Foreign Keys

---

# Pull Request Checklist

✓ Tests

✓ Documentation

✓ Events

✓ Queues

✓ Policies

✓ API Resources

✓ Static Analysis

✓ No Debug Code

---

# Anti-Patterns

Never

Business Logic in Controllers

Business Logic in Models

God Services

Massive Traits

Static Services

Raw SQL Unless Necessary

Hidden Side Effects

---

# Recommended Packages

Laravel Horizon

Laravel Reverb

Laravel Scout

Spatie Permission (if needed)

Spatie Media Library (optional)

Laravel Telescope (non-production)

Laravel Pulse

---

# Claude Code Instructions

When generating backend code:

1. Keep controllers thin.
2. Place business rules in services.
3. Use repositories for persistence.
4. Use DTOs for complex operations.
5. Fire domain events after transactions.
6. Queue expensive work.
7. Return API Resources.
8. Update documentation whenever architecture changes.

---

# Acceptance Criteria

Backend implementation is complete when

✓ Services own business logic.

✓ Controllers remain thin.

✓ Repositories encapsulate persistence.

✓ Events decouple workflows.

✓ Queues handle expensive work.

✓ Authorization is enforced.

✓ Testing accompanies every feature.

✓ Documentation stays synchronized.

---

# Backend Development Workflow

```text
Requirement

↓

DTO

↓

Validation Request

↓

Controller

↓

Service

↓

Repository

↓

Transaction

↓

Domain Event

↓

Queue

↓

API Resource

↓

Tests

↓

Documentation
```

---

# Reference Architecture

```text
React Native

↓

REST API

↓

Controller

↓

Form Request

↓

Policy

↓

Service

↓

Repository

↓

PostgreSQL

↓

Domain Event

↓

Queue

↓

Reverb

↓

Client Update
```

---
