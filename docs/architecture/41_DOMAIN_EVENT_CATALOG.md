# Yowimo Domain Event Catalog

**Version:** 1.0.0

**Status:** Domain-Driven Design Specification

**Priority:** CRITICAL

**Owner:** Backend Platform Team

**Architecture**

Event Driven Architecture (EDA)

Laravel Events

Laravel Listeners

Laravel Queues

Laravel Reverb

**Depends On**

- 08_GAME_ENGINE.md
- 12_WALLET_AND_TOKEN_SYSTEM.md
- 22_BACKEND_SERVICE_CATALOG.md
- 40_WEBSOCKET_EVENT_CATALOG.md

---

# Purpose

This document defines every Domain Event inside Yowimo.

A Domain Event represents something important that has already happened in the business.

Examples

User Registered

Party Started

Reward Granted

Wallet Credited

Marketplace Purchase Completed

These events coordinate different parts of the platform without tightly coupling services.

---

# Event Philosophy

Events describe facts.

Commands request actions.

Example

❌ Bad

```
GrantReward
```

✅ Good

```
RewardGranted
```

Events are immutable.

Events never change.

---

# Event Lifecycle

```text
Controller

↓

Service

↓

Database Transaction

↓

Commit

↓

Fire Domain Event

↓

Listeners

↓

Queues

↓

Realtime

↓

Notifications

↓

Analytics
```

---

# Event Naming Convention

Past tense.

Examples

```
UserRegistered

PartyStarted

RewardGranted

WalletCredited

PurchaseCompleted
```

Never use

```
CreateUser

StartParty

GiveReward
```

---

# Event Structure

Every event contains

```php
event_id

tenant_id

occurred_at

actor_id

aggregate_id

aggregate_type

metadata

version
```

---

# Event Categories

Identity

Social

Gaming

Wallet

Marketplace

Creator

Enterprise

Notifications

Moderation

Analytics

Infrastructure

AI

Sponsors

---

# IDENTITY EVENTS

---

## UserRegistered

Triggered

After successful registration.

Listeners

CreateWallet

CreateProfile

CreatePreferences

GrantWelcomeReward

SendWelcomeNotification

TrackRegistrationAnalytics

---

## UserLoggedIn

Listeners

UpdateLastSeen

TrackAnalytics

UpdatePresence

---

## UserLoggedOut

Listeners

UpdatePresence

CleanupSessions

---

## UserProfileUpdated

Listeners

RefreshRecommendations

UpdateSearchIndex

---

## UserDeleted

Listeners

SoftDeleteResources

CleanupSessions

ArchiveAnalytics

---

# FRIEND EVENTS

---

## FriendRequestSent

Listeners

SendNotification

UpdateAnalytics

---

## FriendRequestAccepted

Listeners

CreateFriendship

AwardReferralBonus

NotifyBothUsers

---

## FriendRemoved

---

## UserBlocked

---

# PARTY EVENTS

---

## PartyCreated

Listeners

GenerateInviteCode

CreateRealtimeRoom

ScheduleNotifications

Analytics

---

## PartyUpdated

Listeners

RefreshPartyCache

BroadcastUpdate

---

## PartyStarted

Listeners

InitializeGame

BroadcastPartyStarted

TrackAnalytics

NotifyPlayers

AIHostWelcome

---

## PartyPaused

---

## PartyResumed

---

## PartyCompleted

Listeners

GenerateHighlights

AwardRewards

UpdateLeaderboard

GenerateSummary

MarketplaceAchievements

Analytics

---

## PartyCancelled

Listeners

RefundTokens

NotifyPlayers

CleanupResources

---

# PLAYER EVENTS

---

## PlayerJoinedParty

Listeners

PresenceUpdate

BroadcastPlayerJoined

Analytics

---

## PlayerLeftParty

---

## PlayerReady

---

## PlayerDisconnected

---

## PlayerReconnected

---

# GAME EVENTS

---

## GameStarted

Listeners

InitializeRounds

StartCountdown

BroadcastGame

---

## RoundStarted

---

## TurnStarted

---

## CardRevealed

Listeners

Analytics

ModerationTracking

---

## ChallengeCompleted

Listeners

UpdateScore

GrantXP

TrackCompletion

---

## ChallengeSkipped

Listeners

DeductTokens

TrackSkip

---

## RoundCompleted

---

## GameCompleted

Listeners

CalculateWinner

AwardAchievements

GenerateSummary

LeaderboardUpdate

BroadcastResults

---

# SCORE EVENTS

---

## ScoreUpdated

---

## WinnerDeclared

Listeners

Rewards

Notifications

Analytics

---

# WALLET EVENTS

---

## WalletCreated

Triggered

Immediately after registration.

---

## WalletCredited

Listeners

UpdateBalance

Notification

Analytics

FraudDetection

---

## WalletDebited

Listeners

LedgerVerification

Analytics

---

## RewardGranted

Listeners

WalletCredit

PushNotification

CelebrateAnimation

Analytics

---

## RewardClaimed

---

## TokensPurchased

Listeners

CreditWallet

Analytics

ReceiptEmail

---

## WalletReconciled

---

# MARKETPLACE EVENTS

---

## MarketplaceItemPublished

Listeners

SearchIndex

Notifications

Analytics

---

## MarketplacePurchaseStarted

---

## MarketplacePurchaseCompleted

Listeners

GrantOwnership

CreatorRevenue

WalletUpdate

Analytics

EmailReceipt

---

## MarketplacePurchaseRefunded

Listeners

InventoryRollback

WalletRefund

Analytics

---

## InventoryUpdated

---

# CREATOR EVENTS

---

## CreatorApplied

Listeners

ModerationQueue

AdminNotification

---

## CreatorApproved

Listeners

EnableDashboard

WelcomeEmail

Analytics

---

## CreatorRejected

---

## CreatorPackPublished

Listeners

MarketplaceIndex

Search

Recommendations

---

## CreatorPayoutGenerated

---

## CreatorPayoutCompleted

---

# AI EVENTS

---

## AIHostGenerated

Listeners

RealtimeBroadcast

Analytics

---

## AITranslationCompleted

---

## AIRecommendationGenerated

---

## AISummaryGenerated

---

## AIModerationCompleted

---

# CHAT EVENTS

---

## ConversationCreated

---

## MessageSent

Listeners

RealtimeBroadcast

Notification

Moderation

Analytics

---

## MessageEdited

---

## MessageDeleted

---

# NOTIFICATION EVENTS

---

## NotificationCreated

Listeners

PushNotification

EmailNotification

RealtimeBroadcast

---

## NotificationRead

---

# MODERATION EVENTS

---

## ContentReported

Listeners

ModerationQueue

AdminNotification

Analytics

---

## ModerationActionTaken

---

## UserSuspended

Listeners

TerminateSessions

NotifyUser

AuditLog

---

## UserBanned

Listeners

InvalidateTokens

CleanupPresence

---

# ORGANIZATION EVENTS

---

## OrganizationCreated

Listeners

ProvisionWorkspace

Analytics

---

## EmployeeInvited

---

## EmployeeJoined

---

## CorporateEventStarted

---

## CorporateEventCompleted

---

# SPONSOR EVENTS

---

## SponsorCampaignCreated

---

## SponsorCampaignStarted

---

## SponsorRewardGranted

Listeners

WalletCredit

Analytics

Notification

---

## SponsorCampaignCompleted

---

# REFERRAL EVENTS

---

## ReferralCreated

---

## ReferralCompleted

Listeners

GrantReferralReward

Analytics

---

# PAYMENT EVENTS

---

## PaymentInitialized

---

## PaymentSucceeded

Listeners

CreditWallet

IssueReceipt

Analytics

---

## PaymentFailed

Listeners

RetryPolicy

Notification

---

## PaymentRefunded

---

# STORAGE EVENTS

---

## FileUploaded

Listeners

GenerateThumbnail

VirusScan

OptimizeImage

Analytics

---

## FileDeleted

---

# ANALYTICS EVENTS

---

## AnalyticsEventTracked

Queued.

Never synchronous.

---

## LeaderboardUpdated

---

## DailyMetricsGenerated

---

# INFRASTRUCTURE EVENTS

---

## DeploymentCompleted

---

## FeatureFlagEnabled

---

## MaintenanceStarted

---

## MaintenanceCompleted

---

# EVENT ORDERING

Critical Events

Wallet

Payments

Marketplace

must execute sequentially.

Non-critical events

Analytics

Notifications

Recommendations

may execute in parallel.

---

# TRANSACTION RULE

Events fire

Only

After successful database commit.

Never fire inside an uncommitted transaction.

---

# QUEUE STRATEGY

High Priority

Wallet

Payments

Security

Medium

Gameplay

Marketplace

Notifications

Low

Analytics

Recommendations

Reports

---

# IDEMPOTENCY

Every event contains

```
event_id
```

Listeners must ignore duplicate event IDs.

---

# EVENT VERSIONING

Every event includes

```
version
```

Example

```
1.0.0
```

Breaking payload changes require a new version.

---

# FAILURE HANDLING

Listener fails

↓

Retry

↓

Dead Letter Queue

↓

Alert Engineering

↓

Manual Replay

---

# EVENT REPLAY

Future Support

Replay events

From Event Store

For

Recovery

Debugging

Analytics

New Services

---

# AUDIT

Every critical event creates

Audit Log

Including

Actor

Action

Entity

Timestamp

Tenant

Metadata

---

# MONITORING

Track

Events Fired

Listener Duration

Queue Time

Failures

Retries

Dead Letters

Replay Count

---

# SECURITY

Never include

Passwords

Secrets

Payment Credentials

Private Messages

JWT Tokens

inside event payloads.

---

# FUTURE EVENTS

```
AchievementUnlocked

BadgeEarned

QuestCompleted

TournamentStarted

TournamentCompleted

SeasonStarted

SeasonCompleted

GuildCreated

GuildJoined

PluginInstalled

DeveloperAppAuthorized

LiveEventStarted

LiveEventEnded

ARSessionStarted

VRSessionCreated
```

---

# Claude Code Instructions

When implementing domain events:

1. Fire events only after successful transactions.
2. Keep events immutable.
3. Use past-tense names.
4. Keep payloads minimal but meaningful.
5. Queue expensive listeners.
6. Make listeners idempotent.
7. Document every new event.
8. Update this catalog whenever domain events are added or changed.

---

# Acceptance Criteria

The Domain Event Catalog is complete when:

- Every business event is documented.
- Events follow consistent naming.
- Listeners are clearly defined.
- Queue priorities are specified.
- Event ordering is documented.
- Replay and monitoring strategies are established.
- New services can subscribe to events without modifying existing business logic.

---
