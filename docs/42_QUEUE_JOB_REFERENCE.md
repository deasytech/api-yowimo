# Yowimo Queue Job Reference

**Version:** 1.0.0

**Status:** Background Processing Specification

**Priority:** CRITICAL

**Owner:** Backend Platform Team

**Framework**

Laravel Queues

Laravel Horizon

Redis

Supervisor

**Depends On**

- 10_QUEUE_AND_BACKGROUND_JOBS.md
- 22_BACKEND_SERVICE_CATALOG.md
- 41_DOMAIN_EVENT_CATALOG.md

---

# Purpose

This document defines every asynchronous job executed by Yowimo.

Queue jobs are responsible for long-running, resource-intensive, or non-blocking work.

Examples

- Notifications
- AI
- Video
- Image Processing
- Wallet Rewards
- Analytics
- Marketplace Processing

No queue job should exist without documentation.

---

# Philosophy

Requests should return quickly.

Heavy work belongs in queues.

If a user does not need an immediate response, queue it.

---

# Queue Architecture

```text
REST Request

↓

Controller

↓

Service

↓

Domain Event

↓

Queue Job

↓

Worker

↓

Database

↓

Broadcast

↓

Notification
```

---

# Queue Drivers

Production

Redis

Development

Sync

Testing

Fake Queue

Future

Amazon SQS

RabbitMQ

Kafka

---

# Queue Names

```
critical

wallet

payments

notifications

marketplace

ai

analytics

media

email

sms

voice

video

sponsors

creator

enterprise

reports

maintenance

low
```

---

# Priority Order

Highest

```
critical
```

↓

wallet

↓

payments

↓

marketplace

↓

notifications

↓

voice

↓

video

↓

ai

↓

analytics

↓

reports

↓

maintenance

↓

low

---

# Job Naming Convention

Every job ends with

```
Job
```

Examples

```
GenerateHighlightsJob

CreditWalletJob

PublishMarketplaceItemJob

ModerateContentJob

SendPushNotificationJob
```

---

# Job Lifecycle

```text
Dispatch

↓

Queued

↓

Reserved

↓

Running

↓

Completed

↓

Deleted
```

Failure

↓

Retry

↓

Dead Letter Queue

↓

Alert

---

# Wallet Jobs

---

## CreateWalletJob

Queue

wallet

Triggered By

UserRegistered

Responsibilities

Create wallet

Initialize balance

Create ledger

---

## CreditWalletJob

Queue

wallet

Triggered By

RewardGranted

PaymentSucceeded

ReferralCompleted

Responsibilities

Credit balance

Create ledger entry

Broadcast update

---

## DebitWalletJob

Queue

wallet

Responsibilities

Deduct balance

Ledger

Fraud validation

---

## ReconcileWalletJob

Runs

Nightly

Purpose

Verify ledger integrity.

---

# Payment Jobs

---

## ProcessPaymentJob

Queue

payments

Responsibilities

Verify payment

Credit wallet

Store transaction

---

## RefundPaymentJob

---

## VerifyWebhookJob

---

## GenerateReceiptJob

---

# Marketplace Jobs

---

## PurchaseMarketplaceItemJob

Responsibilities

Validate ownership

Create purchase

Grant inventory

Creator revenue

Analytics

---

## PublishMarketplaceItemJob

---

## UpdateMarketplaceRankingJob

Runs

Hourly

---

## ProcessCreatorRoyaltyJob

---

## GenerateMarketplaceRecommendationsJob

---

# Creator Jobs

---

## ApproveCreatorJob

---

## RejectCreatorJob

---

## CalculateCreatorRevenueJob

Runs

Nightly

---

## GenerateCreatorPayoutJob

Runs

Monthly

---

## ExportCreatorTaxReportJob

Runs

Yearly

---

# AI Jobs

---

## GenerateAIHostResponseJob

Queue

ai

Purpose

Generate host narration.

---

## GeneratePartySummaryJob

---

## GenerateHighlightsJob

---

## TranslateContentJob

---

## ModerateAIResponseJob

---

## GenerateRecommendationsJob

---

## GenerateCorporateIcebreakersJob

---

# Notification Jobs

---

## SendPushNotificationJob

---

## SendEmailNotificationJob

---

## SendSMSNotificationJob

Future

---

## BroadcastRealtimeNotificationJob

---

## SendReminderJob

---

# Game Jobs

---

## InitializeGameSessionJob

---

## CalculateGameResultsJob

---

## AwardGameRewardsJob

---

## CloseExpiredPartyJob

Runs

Every Minute

---

# Analytics Jobs

---

## RecordAnalyticsEventJob

---

## AggregateDailyMetricsJob

Runs

Nightly

---

## GenerateLeaderboardJob

Runs

Every 5 Minutes

---

## GenerateExecutiveReportsJob

Runs

Weekly

---

# Media Jobs

---

## OptimizeImageJob

Queue

media

---

## GenerateThumbnailJob

---

## CompressVideoJob

---

## ExtractAudioWaveformJob

---

## VirusScanUploadJob

---

# Voice Jobs

---

## CleanupVoiceRoomsJob

Runs

Every Hour

---

## ArchiveVoiceRecordingJob

Future

---

# Video Jobs

---

## CleanupVideoSessionsJob

---

## GenerateReplayClipJob

Future

---

# Chat Jobs

---

## ModerateMessageJob

---

## ArchiveConversationJob

---

## DeleteExpiredMessagesJob

---

# Referral Jobs

---

## ProcessReferralRewardJob

---

## GenerateReferralStatisticsJob

---

# Sponsor Jobs

---

## ActivateSponsorCampaignJob

---

## ExpireSponsorCampaignJob

---

## CreditSponsorRewardJob

---

## GenerateSponsorReportJob

---

# Enterprise Jobs

---

## ProvisionOrganizationJob

---

## ImportEmployeesJob

---

## GenerateTrainingReportJob

---

## ArchiveCorporateEventsJob

---

# Maintenance Jobs

---

## CleanupExpiredTokensJob

Runs

Daily

---

## CleanupOldNotificationsJob

---

## CleanupTemporaryFilesJob

---

## CleanupOldAnalyticsJob

---

## RotateLogsJob

---

## RefreshSearchIndexJob

---

# Infrastructure Jobs

---

## HealthCheckJob

Runs

Every Minute

---

## VerifyBackupJob

Runs

Daily

---

## RotateSecretsJob

Runs

Quarterly

---

## SyncFeatureFlagsJob

Runs

Every Minute

---

# Retry Policy

Critical

5 Retries

Wallet

5 Retries

Payments

5 Retries

Marketplace

3 Retries

Notifications

3 Retries

AI

2 Retries

Analytics

1 Retry

---

# Backoff Strategy

Example

```
10 Seconds

30 Seconds

60 Seconds

120 Seconds

300 Seconds
```

Exponential backoff.

---

# Timeout

Critical

60 Seconds

Wallet

60 Seconds

Payments

120 Seconds

AI

180 Seconds

Media

600 Seconds

Reports

900 Seconds

---

# Dead Letter Queue

Failed jobs move to

```
failed_jobs
```

Engineering notified immediately for

Wallet

Payments

Marketplace

Security

---

# Idempotency

Every financial job

Must be idempotent.

Never process

Same payment

Same reward

Same purchase

twice.

---

# Monitoring

Track

Queued Jobs

Running Jobs

Failed Jobs

Average Runtime

Retries

Dead Letters

Worker Health

---

# Horizon Configuration

Workers

```
critical

10

wallet

8

payments

6

marketplace

6

notifications

4

ai

4

analytics

2

maintenance

1
```

Adjust automatically based on load.

---

# Scaling Rules

Queue Length

> 500

↓

Increase Workers

Queue Delay

> 30 Seconds

↓

Alert

Failure Rate

> 5%

↓

Investigate

---

# Scheduling

Laravel Scheduler

Examples

Every Minute

Expired Parties

Every Hour

Marketplace Ranking

Daily

Wallet Reconciliation

Weekly

Reports

Monthly

Creator Payouts

Yearly

Tax Reports

---

# Logging

Every job logs

Job ID

Queue

Execution Time

Retries

Result

Memory

Duration

---

# Security

Never serialize

Passwords

Secrets

Payment Credentials

JWT Tokens

Private Keys

---

# Future Jobs

```
GenerateAchievementsJob

TournamentBracketJob

QuestResetJob

SeasonResetJob

BadgeAwardJob

GuildMaintenanceJob

PluginSyncJob

EdgeCacheRefreshJob

OfflineSyncJob

AIModelTrainingJob
```

---

# Claude Code Instructions

When implementing queue jobs:

1. Keep jobs focused on a single responsibility.
2. Make jobs idempotent where required.
3. Use appropriate queues based on priority.
4. Configure retries and exponential backoff.
5. Log execution metrics.
6. Dispatch jobs from services or event listeners, not controllers.
7. Keep payloads lightweight.
8. Update this document whenever a new job is introduced.

---

# Acceptance Criteria

The Queue Job Reference is complete when:

- Every asynchronous job is documented.
- Queue priorities are clearly defined.
- Retry and timeout policies are established.
- Monitoring and alerting are configured.
- Financial jobs are idempotent.
- Workers scale automatically based on load.
- New background tasks follow standardized conventions.

---
