# Yowimo Notification System

**Version:** 1.0.0

**Status:** Core Platform Specification

**Priority:** HIGH

**Owner:** Platform Communications Team

**Depends On**

- 05_API_STANDARDS.md
- 07_EVENT_CATALOG.md
- 09_REALTIME_ARCHITECTURE.md
- 10_QUEUE_AND_BACKGROUND_JOBS.md

---

# Purpose

Notifications keep players connected before, during and after every party.

The notification system is responsible for delivering the right message through the right channel at the right time.

Notifications should increase engagement, not annoy users.

---

# Notification Philosophy

Every notification must satisfy four rules.

✓ Relevant

✓ Timely

✓ Actionable

✓ Respectful

---

Never send unnecessary notifications.

---

# Notification Architecture

```text
Business Event

↓

Notification Service

↓

Template Engine

↓

Channel Selection

↓

Queue

↓

Delivery Provider

↓

User
```

---

# Notification Types

Transaction

Gameplay

Social

Marketing

System

Security

Corporate

Sponsor

AI

---

# Delivery Channels

Current

Push

In-App

Email

Future

SMS

WhatsApp

Telegram

Discord

Slack

Microsoft Teams

Apple Watch

Android Wear

---

# Notification Categories

Party

Friends

Wallet

Marketplace

Rewards

Achievements

Sponsors

Security

Announcements

Updates

---

# Transactional Notifications

Examples

Party Invitation

Party Starting

Purchase Completed

Reward Granted

Wallet Credited

Friend Request

Password Changed

Email Verified

These cannot be disabled.

---

# Marketing Notifications

Examples

New Card Packs

Seasonal Events

Discounts

Weekly Challenges

Trending Games

Creator Packs

Sponsored Events

Users may opt out.

---

# Social Notifications

Examples

Friend Online

Friend Joined Party

Mentioned In Chat

Comment Received

Highlight Shared

Invitation Accepted

---

# Gameplay Notifications

Examples

Your Turn

Round Starting

Game Starting

Timer Warning

Vote Open

Challenge Completed

MVP Announced

Party Summary Ready

---

# Wallet Notifications

Examples

Tokens Earned

Tokens Spent

Purchase Successful

Refund Issued

Sponsor Credit

Reward Claimed

---

# Marketplace Notifications

Examples

Purchase Delivered

Flash Sale

New Bundle

Limited Offer

Wishlist Discount

Creator Release

---

# Security Notifications

Examples

New Login

New Device

Password Changed

Suspicious Activity

Account Locked

MFA Enabled

Email Changed

---

# Notification Entity

Fields

```
id

user_id

type

category

title

body

priority

channel

status

metadata

created_at
```

---

# Delivery Status

Queued

Sending

Delivered

Read

Failed

Expired

Dismissed

---

# Notification Priority

Critical

High

Normal

Low

Background

---

Priority determines queue selection.

---

# Template Engine

Every notification uses a template.

Template Fields

Title

Body

Localization

Variables

Actions

Fallback

---

Example

Title

```
Your party starts in 10 minutes!
```

Variables

```
party_name

host_name

time_remaining
```

---

# Localization

Templates support

English

French

Spanish

Portuguese

Arabic

Yoruba

Igbo

Hausa

Future

100+ Languages

---

# Rich Notifications

Support

Images

Avatars

Buttons

Deep Links

Countdowns

Progress

Future Video

---

# Deep Linking

Every actionable notification opens the correct screen.

Examples

```
Party Invitation

↓

Party Lobby
```

```
Reward Granted

↓

Wallet
```

```
Purchase Completed

↓

Marketplace Inventory
```

---

# Notification Preferences

Users control

Friend Activity

Marketing

Promotions

Email

Push

AI Suggestions

Marketplace

Daily Digest

Weekly Summary

---

# Quiet Hours

Users may configure

Start Time

End Time

Timezone

Critical notifications ignore quiet hours.

---

# Digest Notifications

Instead of sending

10 notifications

↓

Combine

↓

One summary notification

Example

```
5 friends joined parties today.
```

---

# Scheduled Notifications

Examples

Party Reminder

24 Hours Before

1 Hour Before

10 Minutes Before

Countdown

Birthday Events

Seasonal Promotions

---

# AI Notifications

AI generates

Party Recap

Weekly Highlights

Personalized Recommendations

Challenge Suggestions

Friend Suggestions

Marketplace Suggestions

---

# Read Receipts

Supported for

In-App Notifications

Read Timestamp stored.

---

# Delivery Providers

Push

Firebase Cloud Messaging

Apple Push Notification Service

Email

Amazon SES

Future

Postmark

SendGrid

SMS

Twilio

Future WhatsApp

Twilio WhatsApp

Meta Business API

---

# Queue Architecture

Every notification is queued.

High Priority Queue

Security

Wallet

Party

Normal Queue

Marketplace

Social

Marketing

---

# Retry Policy

Retries

1

5

15

30

60 Minutes

After retries

↓

Mark Failed

↓

Log

---

# Expiration

Some notifications expire.

Examples

Party Invitation

Expires

↓

Party Starts

Flash Sale

Expires

↓

Sale Ends

---

# Badge Counts

Maintain

Unread Count

Per User

Real-time updates through Reverb.

---

# Events

Notification system listens to

```
party.created

party.started

friend.requested

wallet.credited

purchase.completed

reward.granted

achievement.unlocked

highlight.generated
```

---

# Notification Events

Produces

```
notification.created

notification.sent

notification.delivered

notification.failed

notification.read
```

---

# Analytics

Track

Delivery Rate

Open Rate

CTR

Conversion

Unsubscribes

Failures

Latency

---

# Monitoring

Alert on

Provider Failure

Queue Backlog

High Failure Rate

Push Token Errors

Email Bounce Rate

---

# Security

Never include

Passwords

Verification Codes (outside OTP flow)

Payment Details

Private Messages

Sensitive Internal Data

in notification payloads.

---

# Future Features

AI Notification Writer

Smart Send Time

Location-Based Notifications

Wearables

Live Activities (iOS)

Android Live Notifications

Voice Notifications

Smart Digest

Cross-Device Sync

---

# Claude Code Instructions

When implementing notifications:

1. Publish notifications from domain events.
2. Use templates.
3. Respect user preferences.
4. Queue all deliveries.
5. Retry failures.
6. Localize content.
7. Track delivery status.
8. Update this document when adding new channels or notification types.

---

# Acceptance Criteria

The Notification System is complete when:

- Every major business event can trigger notifications.
- Users control notification preferences.
- Delivery is reliable and observable.
- Notifications support localization.
- Rich content and deep links are available.
- Marketing and transactional notifications remain separate.

---
