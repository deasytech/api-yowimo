# Yowimo Backend Service Catalog

**Version:** 1.0.0

**Status:** Core Engineering Specification

**Priority:** CRITICAL

**Owner:** Backend Engineering

**Applies To**

Laravel 12 Backend

---

# Purpose

This document defines every backend service that exists inside Yowimo.

A Service represents a business capability.

Every business action must belong to exactly one Service.

---

# Service Philosophy

Services contain business logic.

Controllers do not.

Models do not.

Repositories do not.

Events do not.

Services orchestrate everything.

---

# Service Architecture

```text
API

↓

Controller

↓

Service

↓

Repository

↓

Database
```

Services may also

Dispatch Jobs

Emit Events

Call AI

Broadcast

Publish Notifications

---

# Dependency Rules

Allowed

```
Controller

↓

Service

↓

Repository

↓

Model
```

Never

```
Service

↓

Controller
```

Never

```
Repository

↓

Service
```

Never circular dependencies.

---

# Service Registry

Core Platform

AuthenticationService

UserService

ProfileService

FriendService

PartyService

InvitationService

GameService

CardService

RoundService

TurnService

RewardService

WalletService

TransactionService

MarketplaceService

PurchaseService

InventoryService

SponsorService

AdvertisementService

ReferralService

NotificationService

RealtimeService

AIOrchestratorService

VoiceService

HighlightService

LeaderboardService

ModerationService

AnalyticsService

StorageService

SettingsService

AuditService

SearchService

LocalizationService

CorporateService

TournamentService (Future)

CreatorService (Future)

---

# AuthenticationService

## Responsibility

Authentication lifecycle.

---

### Responsibilities

Register

Login

Logout

Refresh Tokens

Verify Email

Reset Password

MFA

Session Validation

---

### Depends On

UserRepository

NotificationService

AuditService

---

### Emits

```
user.registered

user.logged_in

user.logged_out

password.reset

email.verified
```

---

### Public Methods

```
register()

login()

logout()

refresh()

verifyEmail()

forgotPassword()

resetPassword()

enableMFA()

disableMFA()
```

---

# UserService

## Responsibility

User lifecycle.

---

Handles

Profile

Preferences

Status

Verification

Deletion

---

### Emits

```
user.updated

user.deleted

user.verified
```

---

### Public Methods

```
updateProfile()

updateSettings()

deleteAccount()

verifyIdentity()

restoreAccount()
```

---

# FriendService

Handles

Friend Requests

Accept

Reject

Block

Unblock

Suggestions

Mutual Friends

---

### Methods

```
sendRequest()

accept()

reject()

remove()

block()

suggestFriends()
```

---

### Emits

```
friend.requested

friend.accepted

friend.removed
```

---

# PartyService

## Responsibility

Everything about Parties.

---

Handles

Create Party

Update

Join

Leave

Cancel

Archive

Scheduling

Visibility

---

### Depends On

InvitationService

GameService

RealtimeService

NotificationService

---

### Emits

```
party.created

party.updated

party.started

party.ended

party.cancelled
```

---

### Methods

```
create()

update()

join()

leave()

start()

end()

cancel()

archive()
```

---

# InvitationService

Handles

Invite Friends

Invite Links

QR Codes

Invite Expiration

Reminder Notifications

---

### Methods

```
invite()

accept()

decline()

expire()

generateQRCode()
```

---

# GameService

The heart of gameplay.

---

Handles

Game Sessions

Rules

Modes

Lifecycle

Configuration

---

### Depends On

RoundService

CardService

RewardService

RealtimeService

---

### Emits

```
game.started

game.paused

game.completed
```

---

### Methods

```
start()

pause()

resume()

finish()

configure()

restart()
```

---

# RoundService

Controls

Rounds

Preparation

Execution

Completion

---

Methods

```
createRound()

startRound()

finishRound()
```

---

# TurnService

Controls

Turn Order

Timer

Player State

Voting

Completion

---

Methods

```
nextTurn()

skip()

complete()

timeout()

restore()
```

---

# CardService

Controls

Card Pools

Selection

Randomization

Difficulty

Filtering

---

Methods

```
draw()

shuffle()

unlock()

filter()

generate()
```

---

# RewardService

Calculates

Rewards

Achievements

Bonuses

Daily Rewards

---

Methods

```
calculate()

grant()

claim()

expire()
```

---

# WalletService

Single source of truth.

Only WalletService modifies balances.

---

Methods

```
credit()

debit()

reserve()

release()

refund()

freeze()
```

---

Emits

```
wallet.credited

wallet.debited
```

---

# TransactionService

Responsible for

Financial Transactions

Audit

History

Status

---

Methods

```
create()

complete()

cancel()

refund()

find()
```

---

# MarketplaceService

Manages

Catalog

Products

Bundles

Recommendations

---

Methods

```
browse()

feature()

publish()

archive()

recommend()
```

---

# PurchaseService

Purchase orchestration.

---

Methods

```
purchase()

verify()

refund()

deliver()
```

---

# InventoryService

Owns

Purchased Assets

Card Packs

Themes

Voice Packs

Unlockables

---

Methods

```
grant()

revoke()

owned()

refresh()
```

---

# SponsorService

Handles

Sponsors

Campaigns

Credits

Budgets

Reports

---

Methods

```
createCampaign()

fund()

creditPlayers()

closeCampaign()
```

---

# AdvertisementService

Responsible for

Rewarded Ads

Verification

Fraud Detection

Rewards

---

Methods

```
verifyReward()

grantReward()

trackView()
```

---

# ReferralService

Handles

Invite Codes

Rewards

Fraud Detection

---

Methods

```
createCode()

redeem()

reward()

validate()
```

---

# NotificationService

Supports

Push

Email

In-App

Scheduling

Localization

---

Methods

```
send()

schedule()

cancel()

markRead()
```

---

# RealtimeService

Abstraction over Reverb.

---

Methods

```
broadcast()

presence()

private()

public()
```

---

# AIOrchestratorService

Coordinates

Prompt Builder

Provider

Voice

Translation

Moderation

Summaries

---

Methods

```
generate()

moderate()

translate()

summarize()

recommend()
```

---

# VoiceService

Responsible for

Voice Generation

Playback

Streaming

Voice Packs

---

Methods

```
generateVoice()

stream()

cache()
```

---

# HighlightService

Creates

Highlights

Thumbnails

Captions

Recaps

---

Methods

```
generate()

publish()

archive()
```

---

# LeaderboardService

Calculates

Rankings

Scores

Season Rankings

Achievements

---

Methods

```
calculate()

refresh()

seasonReset()
```

---

# ModerationService

Handles

Reports

Warnings

Bans

Appeals

Trust Score

---

Methods

```
report()

warn()

ban()

review()

appeal()
```

---

# AnalyticsService

Responsible for

Events

KPIs

Funnels

Dashboards

---

Methods

```
track()

aggregate()

report()

export()
```

---

# StorageService

Handles

S3

Uploads

Downloads

Image Processing

Media URLs

---

Methods

```
upload()

delete()

optimize()

signedUrl()
```

---

# SettingsService

Application Settings

Feature Flags

Configuration

Regional Rules

---

Methods

```
get()

set()

cache()

refresh()
```

---

# AuditService

Responsible for

Audit Logs

Compliance

History

Tracing

---

Methods

```
record()

find()

export()
```

---

# SearchService

Global Search

Users

Games

Marketplace

Friends

Cards

---

Methods

```
search()

index()

rebuild()
```

---

# LocalizationService

Supports

Languages

Translations

Regional Content

---

Methods

```
translate()

supportedLanguages()

localize()
```

---

# CorporateService

Corporate Parties

Organizations

Licensing

Departments

Employees

---

Methods

```
createOrganization()

inviteEmployees()

generateReport()
```

---

# Service Communication Rules

Services communicate through

Events

or

Direct Dependency

Never

Controller

↓

Controller

Never

Service

↓

Controller

---

# Queue Usage

Heavy services queue work.

Examples

AI

Highlights

Notifications

Analytics

Video

Emails

---

# Security

Every Service

Validates Authorization

Uses Transactions

Logs Audit Events

Handles Exceptions

Never trusts client input.

---

# Testing

Every Service requires

Unit Tests

Feature Tests

Integration Tests (where applicable)

Critical services require

95% Coverage

---

# Claude Code Instructions

When generating backend code:

1. Every business capability belongs to a Service.
2. Never duplicate business logic.
3. Inject Services.
4. Emit domain events.
5. Keep services cohesive.
6. Keep services independently testable.
7. Document new services here.
8. Never bypass WalletService for financial operations.

---

# Acceptance Criteria

The Backend Service Catalog is complete when:

- Every business capability maps to exactly one service.
- Service responsibilities are clearly defined.
- Dependencies remain unidirectional.
- New developers can understand the backend architecture from this document alone.

---
