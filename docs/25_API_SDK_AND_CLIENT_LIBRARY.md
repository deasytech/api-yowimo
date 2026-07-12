# Yowimo API SDK & Client Library

**Version:** 1.0.0

**Status:** Core Engineering Specification

**Priority:** CRITICAL

**Owner:** Platform Engineering

**Applies To**

- React Native App
- React Web
- TV Application
- Admin Portal
- Future SDKs

---

# Purpose

Every Yowimo client communicates with the backend through a single, standardized SDK.

Clients should never communicate with HTTP endpoints directly.

The SDK provides:

- Type Safety
- Authentication
- Error Handling
- Retry Logic
- Offline Support
- Request Logging
- Automatic Token Refresh

---

# Philosophy

Applications should think in terms of

```text
WalletService.credit()

PartyService.join()

Marketplace.purchase()
```

not

```text
POST /api/v1/wallet/credit
```

The API is an implementation detail.

---

# Architecture

```text
UI

↓

Hook

↓

SDK

↓

HTTP Client

↓

Backend API
```

---

# SDK Folder Structure

```
sdk/

    auth/

    users/

    parties/

    games/

    cards/

    wallet/

    marketplace/

    notifications/

    ai/

    leaderboard/

    sponsors/

    uploads/

    realtime/

    analytics/

    shared/
```

---

# Core Modules

Authentication

Users

Friends

Parties

Games

Cards

Wallet

Marketplace

Sponsors

Notifications

Leaderboard

AI

Uploads

Realtime

Analytics

Settings

Localization

---

# HTTP Client

Base Client

Axios

Responsibilities

Authentication

Headers

Timeouts

Retries

Logging

Error Mapping

Cancellation

---

# Base Configuration

Example

```ts
const api = createApiClient({
    baseURL,
    timeout,
    headers,
});
```

Every request passes through this client.

---

# Authentication

Automatically

Attach Access Token

↓

Refresh Expired Token

↓

Retry Original Request

↓

Logout if Refresh Fails

---

# Authentication Flow

```text
Login

↓

Access Token

↓

Refresh Token

↓

Expired

↓

Refresh

↓

Continue
```

---

# Request Lifecycle

```text
Hook

↓

SDK

↓

Validation

↓

Request Interceptor

↓

HTTP

↓

Response Interceptor

↓

Typed Response
```

---

# Response Structure

All responses conform to

```ts
ApiResponse<T>;
```

Example

```ts
{
    success: true,

    data: {},

    meta: {},

    message: ""
}
```

---

# Error Structure

Example

```ts
{
    success:false,

    error:{
        code,
        message,
        details
    }
}
```

---

# Error Types

Validation

Authentication

Authorization

Rate Limit

Network

Timeout

Server

Unknown

---

# Error Mapping

HTTP

↓

Domain Error

↓

UI Message

Never expose raw backend errors.

---

# Pagination

Cursor Based

```text
Next Cursor

↓

Fetch

↓

Merge
```

---

# Infinite Lists

Supported

Marketplace

Leaderboard

Friends

Notifications

Highlights

Cards

---

# Query Caching

Managed through React Query.

Examples

```
Wallet

5 Seconds

Friends

30 Seconds

Marketplace

5 Minutes

Settings

1 Hour
```

---

# Offline Strategy

Cache

Wallet

Cards

Friends

Settings

Inventory

Marketplace

Queue

Offline Actions

↓

Replay Automatically

---

# Retry Policy

GET

3 Retries

POST

Idempotent Only

PUT

Retry Once

DELETE

No Retry

---

# Upload SDK

Supports

Images

Videos

Voice

Documents

Profile Photos

Marketplace Assets

---

# Upload Flow

```text
Select File

↓

Compression

↓

Signed URL

↓

Upload

↓

Confirm
```

---

# Download SDK

Supports

Progress

Cancellation

Caching

Resume

---

# Realtime SDK

Abstracts

Laravel Reverb

Provides

Presence

Private Channels

Public Channels

Events

Reconnect

---

Methods

```
connect()

disconnect()

subscribe()

unsubscribe()

broadcast()
```

---

# LiveKit SDK

Handles

Voice

Video

Recording

Participants

Screen Share (Future)

---

# Party SDK

Methods

```
createParty()

joinParty()

leaveParty()

startParty()

invitePlayers()

endParty()
```

---

# Wallet SDK

Methods

```
getBalance()

transactions()

claimReward()

purchaseTokens()
```

---

# Marketplace SDK

Methods

```
browse()

purchase()

owned()

featured()

recommendations()
```

---

# AI SDK

Methods

```
host()

translate()

summarize()

moderate()

recommend()
```

---

# Notification SDK

Methods

```
list()

markRead()

subscribe()

preferences()
```

---

# Leaderboard SDK

Methods

```
global()

friends()

season()

party()
```

---

# Type Safety

Every endpoint exports

Request

Response

Error

Models

Never use

```
any
```

---

# Shared Models

```
User

Party

Wallet

Reward

MarketplaceItem

Notification

Friend

Card

Game

Sponsor
```

Shared across

Mobile

Web

TV

---

# Versioning

SDK

Versioned independently.

Compatible with

API Version

v1

Future

v2

---

# Deprecation Policy

Deprecated APIs remain available

Minimum

12 Months

---

# Logging

SDK logs

Request

Duration

Errors

Retries

Offline Sync

Authentication Refresh

---

# Analytics

Track

Network Failures

Retry Rate

Latency

Cache Hits

Offline Usage

---

# Security

Never expose

Refresh Tokens

Secrets

Admin Endpoints

Internal APIs

---

# Testing

Every SDK Module requires

Unit Tests

Mock API Tests

Integration Tests

Offline Tests

---

# Mock Mode

Support

Development

Storybook

UI Testing

Offline Simulation

---

# Documentation

Every SDK exports

Examples

Interfaces

Method Signatures

Errors

Usage Notes

---

# Future Features

GraphQL SDK

WebSocket SDK

Public SDK

Partner SDK

Corporate SDK

Plugin SDK

OpenAPI Generation

Automatic Client Generation

---

# Claude Code Instructions

When generating SDK code:

1. Never call Axios directly from screens.
2. Use strongly typed models.
3. Handle authentication automatically.
4. Normalize errors.
5. Support offline mode.
6. Cache intelligently.
7. Separate SDK modules by domain.
8. Keep SDK backward compatible.
9. Update this document whenever new API modules are introduced.

---

# Acceptance Criteria

The API SDK is complete when:

- All clients use the SDK.
- HTTP details are hidden from UI.
- Requests are type-safe.
- Authentication is automatic.
- Offline support is available.
- Error handling is standardized.
- New APIs can be added without changing existing consumers.

---
