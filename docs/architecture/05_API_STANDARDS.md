# Yowimo API Standards

**Version:** 1.0.0

**Status:** Living Engineering Specification

**Owner:** Platform Engineering

**Depends On**

- 00_READ_ME_FIRST.md
- 01_PRODUCT_VISION.md
- 02_SYSTEM_ARCHITECTURE.md
- 03_DOMAIN_MODEL.md
- 04_DATABASE_ARCHITECTURE.md

---

# Purpose

This document defines the API standards for the entire Yowimo platform.

Every HTTP endpoint, WebSocket event, mobile request, admin request, third-party integration, and future public API must follow this specification.

The objective is consistency.

A developer should immediately know how every endpoint behaves without reading its implementation.

---

# API Philosophy

Yowimo follows five API principles.

## 1. Predictable

Every endpoint should behave consistently.

Authentication

↓

Validation

↓

Authorization

↓

Business Logic

↓

Standard Response

---

## 2. Versioned

Every public API is versioned.

```
/api/v1

/api/v2
```

Breaking changes never occur inside an existing version.

---

## 3. Stateless

No API endpoint should depend on server memory.

Every request contains everything required.

---

## 4. Resource Oriented

Endpoints represent business resources.

Good

```
GET /parties

POST /parties

PATCH /parties/{id}

DELETE /parties/{id}
```

Bad

```
/createParty

/updateParty

/deleteParty
```

---

## 5. Secure

Every endpoint must require:

Authentication

Authorization

Validation

Rate Limiting

Logging

---

# Base URL

Development

```
http://localhost/api/v1
```

Production

```
https://api.yowimo.com/api/v1
```

---

# Authentication

Authentication is handled by Clerk.

Clients send

```
Authorization

Bearer {JWT}
```

Every request must validate

User

↓

Token

↓

Session

↓

Permissions

---

# Authorization

Authorization uses Laravel Policies.

Controllers never contain permission logic.

Example

```php
$this->authorize('update', $party);
```

Never

```php
if ($party->user_id !== auth()->id()) {
    abort(403);
}
```

---

# HTTP Methods

GET

Retrieve

POST

Create

PATCH

Partial Update

PUT

Full Replacement (rare)

DELETE

Delete

OPTIONS

Preflight

HEAD

Metadata

---

# Resource Naming

Plural

Lowercase

Hyphen separated when necessary.

Good

```
/marketplace-products

/game-sessions

/party-members
```

Bad

```
/MarketplaceProducts

/getParty

/addMember
```

---

# Endpoint Examples

Authentication

```
POST

/auth/login
```

Users

```
GET

/users/me
```

Party

```
POST

/parties
```

Wallet

```
GET

/wallet
```

Marketplace

```
GET

/products
```

Game

```
POST

/game-sessions/{id}/start
```

---

# Standard Response

Success

```json
{
    "success": true,
    "message": "Party created successfully.",
    "data": {},
    "meta": {}
}
```

---

Failure

```json
{
    "success": false,
    "message": "Validation failed.",
    "errors": {}
}
```

---

Never return raw models.

Always use Laravel Resources.

---

# Response Envelope

Every successful response contains

```
success

message

data

meta
```

Optional

```
pagination

links

included
```

---

# Error Responses

400

Bad Request

401

Unauthenticated

403

Forbidden

404

Not Found

409

Conflict

422

Validation Failed

429

Rate Limited

500

Server Error

---

Example

```json
{
    "success": false,
    "message": "You do not have permission to join this party."
}
```

---

# Validation

Every POST

PATCH

PUT

must use Form Requests.

Never validate inside controllers.

---

# Pagination

Default

```
20
```

Maximum

```
100
```

Example

```
GET

/products?page=2
```

Response

```json
{
    "data": [],
    "meta": {
        "current_page": 2,
        "per_page": 20,
        "total": 450,
        "last_page": 23
    }
}
```

---

# Filtering

Examples

```
GET

/products?category=party
```

```
GET

/parties?status=live
```

```
GET

/users?country=NG
```

---

# Searching

Example

```
GET

/products?search=truth
```

Full-text search should use PostgreSQL capabilities where appropriate.

---

# Sorting

Example

```
GET

/products?sort=created_at
```

Descending

```
GET

/products?sort=-created_at
```

---

# Includes

Relationships are explicitly requested.

```
GET

/parties/123?include=members,host
```

Never eager load unnecessary relationships by default.

---

# Sparse Fields

Allow clients to request only required fields.

Example

```
GET

/users?fields=name,avatar
```

---

# Idempotency

Critical POST requests support idempotency.

Required for

Wallet Purchases

Marketplace Purchases

Sponsor Payments

Token Purchases

Header

```
Idempotency-Key
```

Duplicate requests return the original response.

---

# File Uploads

Multipart

```
multipart/form-data
```

Files stored in S3.

Database stores URLs only.

Maximum sizes

Avatar

10 MB

Highlight Video

500 MB

Party Cover

20 MB

---

# Rate Limiting

Authentication

10/minute

Party Creation

20/hour

Invitations

100/hour

Chat

60/minute

Marketplace Purchase

30/hour

AI Requests

Configurable

---

# Webhooks

Future integrations

Stripe

Google Play

Apple

LiveKit

Webhook requirements

Signature Validation

Replay Protection

Logging

Retries

Idempotency

---

# Realtime Events

Naming convention

```
party.created

party.started

party.updated

party.completed

turn.started

turn.finished

wallet.credited

wallet.debited

reward.granted
```

Always use lowercase dot notation.

---

# API Versioning Policy

Current

```
v1
```

Future

```
v2
```

Rules

Never remove fields from an active version.

Only add optional fields.

Breaking changes require a new version.

---

# API Resources

Every model exposed publicly must have

```
Resource

Collection
```

Never return Eloquent models directly.

---

# Controllers

Controllers should:

Authenticate

Authorize

Validate

Call Service

Return Resource

Nothing else.

---

Example

```php
public function store(
    CreatePartyRequest $request,
    PartyService $service
)
{
    $party = $service->create(
        $request->validated()
    );

    return PartyResource::make($party);
}
```

---

# Services

Services contain

Business Rules

Transactions

Events

Jobs

Policies

Repositories

---

# Logging

Every critical API call logs

Request ID

User ID

IP

Duration

Status Code

Exception

Correlation ID

---

# Correlation IDs

Every request receives

```
X-Correlation-ID
```

Allows tracing across queues and realtime events.

---

# API Documentation

Every endpoint must appear in OpenAPI documentation.

Required

Description

Parameters

Request Body

Responses

Errors

Authentication

Examples

---

# OpenAPI Structure

```
Authentication

Users

Friends

Wallet

Marketplace

Parties

Game

Rewards

Sponsors

Notifications

Admin
```

---

# Deprecation Policy

Deprecated endpoints remain available for at least one major release.

Responses include

```
Deprecation

Sunset
```

headers.

---

# API Security Checklist

✓ HTTPS Only

✓ JWT Authentication

✓ Policy Authorization

✓ Form Requests

✓ Rate Limiting

✓ Input Sanitization

✓ File Validation

✓ SQL Injection Protection

✓ XSS Protection

✓ CSRF (Web)

✓ Audit Logging

---

# Claude Code Instructions

When implementing endpoints:

1. Audit existing routes.
2. Reuse Resources where possible.
3. Never duplicate response formats.
4. Use Form Requests.
5. Use Policies.
6. Call Services only.
7. Return API Resources.
8. Document new endpoints in OpenAPI.
9. Add Feature Tests.
10. Update this document if introducing new API conventions.

---

# Acceptance Criteria

The API layer is considered complete when:

- Every endpoint follows REST conventions.
- Responses use a consistent envelope.
- Authentication and authorization are centralized.
- Validation is handled by Form Requests.
- Controllers remain thin.
- Business logic lives in Services.
- Public APIs are versioned.
- OpenAPI documentation remains synchronized with implementation.

---
