# Yowimo Frontend UI Flow Diagrams

**Version:** 1.0.0

**Status:** Frontend Navigation & UX Specification

**Priority:** CRITICAL

**Owner:** Mobile Engineering / Product Design

**Platform**

React Native

Expo

React Navigation

NativeWind

React Query

Zustand

**Depends On**

- 23_FRONTEND_ARCHITECTURE.md
- 24_DESIGN_SYSTEM.md
- 39_REST_API_REFERENCE.md
- 40_WEBSOCKET_EVENT_CATALOG.md
- 45_SEQUENCE_DIAGRAMS.md

---

# Purpose

This document defines every user journey inside Yowimo.

It explains

- Navigation
- Screen hierarchy
- User journeys
- Deep linking
- Bottom sheets
- Modals
- Authentication flow
- Error states
- Offline flows

Every screen should belong to a documented navigation path.

---

# Navigation Philosophy

Navigation should always be

Simple

Predictable

Fast

Recoverable

Accessible

---

# Navigation Architecture

```text
App

↓

Authentication

↓

Onboarding

↓

Home

↓

Feature

↓

Details

↓

Action

↓

Confirmation
```

---

# Root Navigation

```text
Splash

↓

Authentication

↓

Main App

↓

Modal Stack

↓

Bottom Sheets

↓

Deep Links
```

---

# Authentication Flow

```text
Splash

↓

Onboarding

↓

Welcome

↓

Sign Up

↓

OTP Verification

↓

Create Profile

↓

Permissions

↓

Home
```

---

# Existing User Flow

```text
Splash

↓

Login

↓

Authentication

↓

Sync Profile

↓

Home
```

---

# Password Recovery

```text
Login

↓

Forgot Password

↓

Email / Phone

↓

OTP

↓

Reset Password

↓

Login
```

---

# Guest Flow

```text
Splash

↓

Browse

↓

Explore

↓

Prompt to Sign Up

↓

Authentication

↓

Continue
```

---

# Home Navigation

```text
Home

├── Discover

├── Friends

├── Notifications

├── Wallet

├── Marketplace

├── Profile

└── Create Party
```

---

# Bottom Navigation

```
Home

Discover

Create

Marketplace

Profile
```

Floating Action Button

```
+

Create Party
```

---

# Discover Flow

```text
Discover

↓

Trending

↓

Categories

↓

Game Details

↓

Play

↓

Party Lobby
```

---

# Party Creation Flow

```text
Create Party

↓

Select Game

↓

Party Type

↓

Party Settings

↓

Invite Friends

↓

Confirmation

↓

Lobby
```

---

# Party Join Flow

```text
Invite Link

↓

Join Screen

↓

Lobby

↓

Voice

↓

Countdown

↓

Gameplay
```

---

# Party Lobby

```text
Lobby

↓

Player Ready

↓

Voice Chat

↓

Host Controls

↓

Start Game
```

Host Actions

Start

Pause

Kick

Invite

Settings

---

# Gameplay Flow

```text
Countdown

↓

Round

↓

Turn

↓

Card

↓

Vote

↓

Score

↓

Next Turn

↓

Results
```

---

# Game Completion

```text
Winner

↓

Rewards

↓

Highlights

↓

Statistics

↓

Share

↓

Play Again

↓

Exit
```

---

# Wallet Flow

```text
Wallet

↓

Balance

↓

Transactions

↓

Buy Tokens

↓

Payment

↓

Confirmation
```

---

# Marketplace Flow

```text
Marketplace

↓

Categories

↓

Product

↓

Purchase

↓

Confirmation

↓

Inventory
```

---

# Inventory Flow

```text
Inventory

↓

Owned Packs

↓

Open

↓

Play
```

---

# Creator Flow

```text
Profile

↓

Become Creator

↓

Verification

↓

Dashboard

↓

Publish

↓

Analytics
```

---

# Creator Dashboard

```text
Revenue

↓

Products

↓

Analytics

↓

Followers

↓

Payouts
```

---

# Enterprise Flow

```text
Organization

↓

Workspace

↓

Department

↓

Events

↓

Analytics
```

---

# Corporate Event Flow

```text
Organization

↓

Create Event

↓

Invite Employees

↓

Lobby

↓

Training

↓

Summary
```

---

# Friends Flow

```text
Friends

↓

Requests

↓

Search

↓

Profile

↓

Invite

↓

Party
```

---

# Notifications Flow

```text
Notifications

↓

Notification

↓

Target Screen
```

Examples

Reward

↓

Wallet

Friend Request

↓

Profile

Purchase

↓

Marketplace

---

# Chat Flow

```text
Conversations

↓

Messages

↓

Attachments

↓

Voice

↓

Back
```

---

# Profile Flow

```text
Profile

↓

Edit Profile

↓

Achievements

↓

Statistics

↓

Settings
```

---

# Settings Flow

```text
Settings

↓

Account

↓

Notifications

↓

Privacy

↓

Language

↓

Appearance

↓

Security

↓

Support

↓

About
```

---

# AI Host Flow

```text
Party

↓

AI Host

↓

Voice

↓

Highlights

↓

Recommendations
```

---

# Sponsor Flow

```text
Sponsor Offer

↓

Watch Ad

↓

Reward

↓

Wallet Updated
```

---

# Referral Flow

```text
Referral Center

↓

Invite

↓

Share

↓

Friend Registers

↓

Reward
```

---

# Leaderboard Flow

```text
Leaderboard

↓

Friends

↓

Global

↓

Country

↓

Organization
```

---

# Search Flow

```text
Search

↓

Users

↓

Games

↓

Creators

↓

Marketplace

↓

Organizations
```

---

# Deep Links

Examples

```
yowimo://party/123

yowimo://wallet

yowimo://marketplace/pack/22

yowimo://creator/john

yowimo://organization/abc
```

---

# Modal Flows

Examples

Purchase Confirmation

Invite Friends

Report User

Report Card

Delete Party

Leave Party

Payment Success

---

# Bottom Sheets

Examples

Emoji Picker

Party Actions

Quick Invite

Wallet Actions

Card Options

Message Actions

---

# Empty States

Examples

No Friends

↓

Invite Friends

No Notifications

↓

Explore

No Marketplace Purchases

↓

Browse Marketplace

---

# Offline Flow

```text
Offline

↓

Cached Home

↓

Retry

↓

Reconnect

↓

Sync
```

---

# Error Flow

```text
API Error

↓

Friendly Message

↓

Retry

↓

Support
```

---

# Push Notification Navigation

Examples

Friend Request

↓

Friend Profile

Reward

↓

Wallet

Party Invite

↓

Party Lobby

Creator Follow

↓

Creator Profile

---

# Universal Search Flow

```text
Search

↓

Results

↓

Category

↓

Details

↓

Action
```

---

# Tablet Navigation

```
Sidebar

↓

Content

↓

Detail Panel
```

---

# TV Navigation (Future)

```
QR Join

↓

Lobby

↓

Gameplay

↓

Results
```

---

# Accessibility Flow

Support

Screen Readers

Large Fonts

High Contrast

Reduced Motion

Voice Navigation (Future)

---

# Loading States

Every screen

Must include

Loading

Empty

Error

Success

Refreshing

Offline

---

# Navigation Guards

Examples

Cannot access

Wallet

↓

Not Logged In

Redirect

↓

Authentication

Cannot create party

↓

Incomplete Profile

Redirect

↓

Profile Setup

---

# Future Navigation

```
Guilds

Achievements

Season Pass

Quests

AR Mode

VR Mode

Live Events

Developer Portal

Plugins
```

---

# Navigation Rules

✓ One primary action per screen.

✓ Maximum three taps to any major feature.

✓ Preserve navigation history.

✓ Support deep linking.

✓ Restore state after interruption.

✓ Never lose unsaved progress.

---

# Claude Code Instructions

When implementing frontend navigation:

1. Follow these user flows exactly.
2. Keep navigation predictable.
3. Implement deep links for every major feature.
4. Preserve screen state where appropriate.
5. Support offline recovery.
6. Handle loading and error states consistently.
7. Update this document whenever screens or navigation change.
8. Maintain accessibility across all navigation paths.

---

# Acceptance Criteria

The Frontend UI Flow Diagrams are complete when:

- Every screen belongs to a documented flow.
- Navigation is consistent across the app.
- Deep linking is supported.
- Offline and error flows are defined.
- Loading and empty states exist for every feature.
- Developers can implement navigation without ambiguity.

---
