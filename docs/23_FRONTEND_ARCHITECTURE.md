# Yowimo Frontend Architecture

**Version:** 1.0.0

**Status:** Core Engineering Specification

**Priority:** CRITICAL

**Owner:** Mobile Engineering

**Platform**

React Native (Expo)

TypeScript

NativeWind

React Query

Zustand

Expo Router (Future) / React Navigation

---

# Purpose

The frontend is responsible for delivering a beautiful, fast and responsive social gaming experience.

It should remain:

- Modular
- Predictable
- Offline Friendly
- Highly Performant
- Easy to Maintain
- Accessible

---

# Frontend Philosophy

The UI renders data.

Business logic lives elsewhere.

The frontend never becomes the source of truth.

---

# Technology Stack

Framework

React Native (Expo)

Language

TypeScript

Styling

NativeWind

Networking

Axios

Data Fetching

TanStack React Query

State Management

Zustand

Forms

React Hook Form

Validation

Zod

Animations

React Native Reanimated

Gesture

React Native Gesture Handler

Icons

Lucide React Native

Notifications

Expo Notifications

Realtime

Laravel Reverb

Voice & Video

LiveKit

Maps

React Native Maps

Authentication

Clerk

Storage

MMKV

---

# Application Architecture

```text
App

↓

Navigation

↓

Feature

↓

Screen

↓

Components

↓

Hooks

↓

Services

↓

API Client
```

---

# Folder Structure

```
src/

    app/

    features/

    components/

    navigation/

    services/

    hooks/

    stores/

    lib/

    constants/

    types/

    utils/

    assets/

    providers/

    theme/

    config/

    api/
```

---

# Feature Structure

Example

```
features/

    party/

        screens/

        components/

        hooks/

        services/

        types/

        utils/

        api/
```

Every feature owns its own implementation.

---

# Screen Structure

Each screen contains only

UI

Hooks

Navigation

No heavy business logic.

---

# Component Types

App Components

Feature Components

Shared Components

Layout Components

Animated Components

---

# Shared Components

Examples

Button

Card

Avatar

Badge

Bottom Sheet

Modal

Input

Textarea

Toast

Loader

Skeleton

Chip

Tag

Progress

Countdown

Reaction Bar

---

# Layout Components

Examples

Screen

Header

Footer

SafeArea

Section

EmptyState

ErrorState

LoadingState

---

# Navigation

Primary

Bottom Tabs

Secondary

Stack Navigation

Modal Navigation

Fullscreen Modal

Bottom Sheets

---

Navigation Flow

```text
Splash

↓

Onboarding

↓

Authentication

↓

Home

↓

Party

↓

Gameplay

↓

Results
```

---

# State Management

Global State

Zustand

Examples

Authentication

Theme

Wallet

Settings

Player Presence

Current Party

---

# Server State

React Query

Never store server data inside Zustand.

---

React Query manages

Users

Friends

Parties

Marketplace

Leaderboard

Wallet

Notifications

---

# Local State

useState

Examples

Modal Open

Selected Tab

Search Text

Expanded Card

---

# Forms

Always use

React Hook Form

-

Zod

Never manually validate forms.

---

# API Layer

```text
Screen

↓

Hook

↓

Service

↓

API Client

↓

Backend
```

Never call Axios directly inside screens.

---

# API Client

Responsible for

Authentication

Headers

Retry

Timeout

Interceptors

Error Handling

Token Refresh

---

# Services

Frontend services mirror backend services.

Examples

PartyService

WalletService

MarketplaceService

NotificationService

AIService

GameService

---

# Hooks

Every feature exposes hooks.

Examples

```
useParty()

useWallet()

useLeaderboard()

useMarketplace()

useNotifications()
```

---

# Reusable Hooks

Examples

useDebounce

useCountdown

useClipboard

usePermissions

useLocation

useInfiniteScroll

useRealtime

---

# NativeWind

Always prefer utility classes.

Never create large StyleSheet files.

Inline styles allowed only for

Animations

Transforms

Dynamic Measurements

---

# Theme

Support

Dark Mode

Future

Light Mode

Seasonal Themes

Corporate Themes

Accessibility Themes

---

# Design Tokens

Primary

Secondary

Accent

Danger

Warning

Success

Surface

Background

Text

Border

---

Typography

Heading

Title

Subtitle

Body

Caption

Label

Button

---

# Responsive Design

Support

Phones

Tablets

Foldables (Future)

TV (Future)

---

# Safe Areas

Always use

SafeAreaView

Respect

Top Insets

Bottom Insets

Dynamic Island

Navigation Gestures

---

# Animations

Use Reanimated.

Animation Types

Entrance

Exit

Gesture

Micro Interaction

Loading

Celebration

Card Flip

Countdown

Leaderboard

---

# Performance Rules

Memoize expensive components.

Use

React.memo

useMemo

useCallback

Lazy Loading

FlatList

FlashList

Image Caching

---

# Lists

Always use

FlashList

for

Friends

Marketplace

Leaderboard

Notifications

Chat

---

# Images

Use

expo-image

Support

Caching

BlurHash

Progressive Loading

Fallback

---

# Video

Support

Streaming

Caching

Background Playback

Picture-in-Picture (Future)

---

# Audio

Support

Voice Chat

AI Voice

Sound Effects

Background Music

Mute Controls

---

# Offline Support

Cache

Friends

Wallet

Marketplace

Cards

Settings

Leaderboard

Queue offline actions.

Sync automatically.

---

# Error Handling

Every screen supports

Loading

Empty

Success

Error

Retry

---

# Toasts

Use for

Rewards

Purchases

Errors

Invitations

Achievements

---

# Bottom Sheets

Use for

Party Options

Filters

Wallet

Marketplace

Friends

Profile

---

# Accessibility

Support

VoiceOver

TalkBack

Dynamic Text

Screen Readers

Large Touch Targets

Reduced Motion

High Contrast

---

# Internationalization

Support

RTL

Localization

Date Formatting

Currency Formatting

Regional Text

---

# Notifications

Handle

Push

Foreground

Background

Deep Linking

---

# Realtime

Support

Presence

Chat

Voice

Reactions

Countdown

Game Sync

Leaderboard

---

# Security

Never

Store Secrets

Trust Client State

Expose Admin APIs

Log Sensitive Data

---

# Error Boundary

Every major navigation stack uses

React Error Boundary.

---

# Analytics

Track

Screen Views

Button Clicks

Purchases

Game Events

Navigation

Errors

Performance

---

# Feature Flags

Frontend reads feature flags.

Examples

AI Host

Corporate Mode

Marketplace

Tournament

Creator Tools

---

# Testing

Components

Unit Tests

Hooks

Unit Tests

Navigation

Integration Tests

Critical Screens

E2E Tests

---

# Documentation

Every feature contains

README.md

Architecture Notes

Public API

Examples

---

# Code Generation Rules

When Claude Code generates React Native code:

1. Follow the feature folder structure.
2. Use TypeScript everywhere.
3. Use NativeWind.
4. Use React Query for server state.
5. Use Zustand for global state.
6. Use React Hook Form + Zod.
7. Never call APIs directly from screens.
8. Use reusable components.
9. Follow accessibility standards.
10. Keep components small and composable.

---

# Acceptance Criteria

The Frontend Architecture is complete when:

- Every feature follows the same folder structure.
- Server and local state are clearly separated.
- Components are reusable.
- Screens contain minimal business logic.
- Performance remains smooth on low-end devices.
- Offline support is available for core features.
- Accessibility and localization are built into the architecture.

---
