# Yowimo Queue & Background Jobs

**Version:** 1.0.0

**Status:** Living Engineering Specification

**Priority:** HIGH

**Owner:** Platform Engineering

**Depends On**

- 02_SYSTEM_ARCHITECTURE.md
- 05_API_STANDARDS.md
- 07_EVENT_CATALOG.md
- 09_REALTIME_ARCHITECTURE.md

---

# Purpose

Not every task should happen during an API request.

The purpose of the Queue System is to move expensive operations into the background so that gameplay remains fast and responsive.

Examples include:

- Push notifications
- Emails
- Highlight generation
- AI narration
- Analytics aggregation
- Video processing
- Marketplace fulfillment
- Reward calculations

The API should return immediately while queues handle the heavy work.

---

# Queue Philosophy

Every API request should finish as quickly as possible.

Bad

```text
User joins party

↓

Generate AI Summary

↓

Generate Highlights

↓

Send Push

↓

Send Email

↓

Update Leaderboard

↓

Return Response
```

Good

```text
User joins party

↓

Create Membership

↓

Dispatch Jobs

↓

Return Response

↓

Queues Process Everything Else
```

---

# Queue Driver

Production

```
Redis
```

Development

```
Database

or

Redis
```

Never use

```
sync
```

outside local debugging.

---

# Queue Architecture

```mermaid
flowchart LR

API

↓

Dispatch Job

↓

Redis Queue

↓

Laravel Horizon

↓

Worker

↓

Database

Storage

AI

Notifications
```

---

# Queue Naming

Every queue has one responsibility.

Queues

```
default

notifications

emails

wallet

marketplace

analytics

ai

highlights

video

livekit

cleanup

reports
```

---

# Queue Priorities

Highest

```
wallet
```

↓

```
marketplace
```

↓

```
notifications
```

↓

```
ai
```

↓

```
analytics
```

↓

```
cleanup
```

Financial operations always execute before analytics.

---

# Job Naming

Use action-based names.

Examples

```
SendPartyInvitationJob

GenerateHighlightsJob

CreditWalletJob

PublishPartySummaryJob

CreateLiveKitRoomJob

GenerateAIRecapJob
```

Never

```
PartyJob

Job1

QueueTask

RunStuff
```

---

# Job Structure

Every Job contains

```
Handle()

Retry Policy

Timeout

Unique ID

Logging
```

---

# Job Categories

Authentication

Notifications

Marketplace

Wallet

AI

Highlights

Analytics

Maintenance

Moderation

Media

---

# Notification Jobs

Examples

```
SendPushNotificationJob

SendEmailJob

SendSMSJob

SendDiscordWebhookJob

SendWhatsAppJob
```

---

# AI Jobs

Examples

```
GenerateVoiceJob

GeneratePartySummaryJob

GenerateHighlightsJob

GenerateRecommendationsJob

TranslateChallengeJob

GenerateNewCardsJob
```

---

# Wallet Jobs

Examples

```
CreditWalletJob

DebitWalletJob

CreateLedgerEntryJob

CreateWalletSnapshotJob
```

Financial jobs must execute sequentially.

---

# Marketplace Jobs

```
DeliverPurchaseJob

VerifyReceiptJob

RefundPurchaseJob

GrantPremiumPackJob
```

---

# Highlight Jobs

```
GenerateHighlightClipJob

GenerateThumbnailJob

CompressVideoJob

UploadHighlightJob
```

---

# Analytics Jobs

```
RecordGameplayMetricsJob

AggregateDailyStatsJob

GenerateRetentionReportJob

UpdateLeaderboardsJob
```

---

# Cleanup Jobs

```
DeleteExpiredInvitationsJob

DeleteOldSessionsJob

ArchiveCompletedGamesJob

PruneNotificationsJob
```

---

# LiveKit Jobs

```
CreateRoomJob

CloseRoomJob

GenerateRecordingJob

DeleteRecordingJob
```

---

# Scheduled Jobs

Laravel Scheduler executes recurring work.

Examples

Every Minute

```
ExpireTimers
```

Every Hour

```
Leaderboard Update
```

Daily

```
Analytics Rollup
```

Weekly

```
Sponsor Reports
```

Monthly

```
Cleanup Storage
```

---

# Retry Policy

Default

```
5 attempts
```

Backoff

```
1 min

5 min

15 min

30 min

60 min
```

---

# Timeout Policy

Notification

30 seconds

Wallet

60 seconds

Marketplace

120 seconds

Video

20 minutes

AI

10 minutes

---

# Failed Jobs

Failed jobs move into

```
failed_jobs
```

Never silently discard failed jobs.

---

# Dead Letter Queue

Jobs exceeding retry limits enter DLQ.

Operations dashboard must expose

Job Name

Payload

Attempts

Exception

Replay

Delete

---

# Idempotency

Jobs must safely execute multiple times.

Example

```
Credit Wallet
```

↓

Already Credited?

↓

Ignore

Never double-credit tokens.

---

# Transactions

Critical jobs must use database transactions.

Example

```php
DB::transaction(function () {

    ...

});
```

---

# Chained Jobs

Example

Purchase Flow

```text
Verify Payment

↓

Debit Wallet

↓

Grant Product

↓

Reward User

↓

Send Receipt
```

Each job executes only after the previous succeeds.

---

# Batched Jobs

Example

Party Completed

↓

Generate Highlights

↓

Generate AI Summary

↓

Create Awards

↓

Generate Statistics

↓

Send Notifications

These execute as a batch.

---

# Queue Monitoring

Use Laravel Horizon.

Track

Running Jobs

Failed Jobs

Queue Length

Average Wait

Retries

Memory

Workers

---

# Worker Configuration

Production

```
Supervisor

↓

Laravel Horizon

↓

Redis
```

Workers restart automatically.

---

# Queue Scaling

Current

```
1 Worker
```

↓

```
4 Workers
```

↓

```
Dedicated Queues
```

↓

```
Auto Scaling
```

---

# Memory Limits

Restart worker after

```
500 MB
```

or

```
1000 Jobs
```

whichever comes first.

---

# Logging

Every job logs

Job ID

Correlation ID

Duration

Attempts

Queue

Status

---

# Correlation IDs

Jobs inherit

```
X-Correlation-ID
```

from originating request.

Allows end-to-end tracing.

---

# Queue Metrics

Track

Jobs Per Minute

Average Runtime

Failure Rate

Retry Rate

Queue Delay

Longest Waiting Job

---

# Alerting

Notify engineering when

Queue Delay > 60 sec

Failed Jobs > Threshold

Redis Offline

Workers Offline

Dead Letter Queue Growing

---

# Job Security

Never serialize

JWTs

Passwords

Secrets

Payment Tokens

Sensitive PII

Only serialize identifiers.

---

# Claude Code Instructions

When implementing background processing:

1. Determine if the task should be queued.
2. Keep jobs small and focused.
3. Make jobs idempotent.
4. Use transactions for critical work.
5. Log execution details.
6. Configure retries and timeouts.
7. Monitor through Horizon.
8. Update this document when introducing new queues or jobs.

---

# Acceptance Criteria

Queue architecture is complete when:

- Expensive operations are asynchronous.
- Financial jobs are reliable and idempotent.
- Failed jobs are recoverable.
- Queue health is observable.
- Workers scale independently.
- Background processing never blocks gameplay.

---
