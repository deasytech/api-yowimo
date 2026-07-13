# Yowimo React Native Implementation Guide

**Version:** 1.0.0

**Status:** Mobile Engineering Standards

**Priority:** CRITICAL

**Owner:** Mobile Engineering Team

**Platform**

React Native

Expo SDK

TypeScript

Expo Router

React Query

Zustand

React Hook Form

NativeWind

Expo Notifications

LiveKit

Laravel Backend

---

# Purpose

This guide defines how every React Native feature inside Yowimo must be implemented.

Every engineer and AI coding assistant must follow these standards.

The objective is to ensure every screen feels like it was written by one engineering team.

---

# Engineering Principles

Every feature must be

Simple

Composable

Reusable

Offline Friendly

Accessible

Responsive

Testable

Performant

---

# Technology Stack

Framework

```
React Native
```

Platform

```
Expo
```

Language

```
TypeScript
```

Navigation

```
Expo Router
```

Server State

```
TanStack React Query
```

Client State

```
Zustand
```

Forms

```
React Hook Form
```

Validation

```
Zod
```

Styling

```
NativeWind
```

Animations

```
React Native Reanimated
```

Realtime

```
Laravel Reverb

LiveKit
```

---

# Folder Structure

```
src/

├── app/
├── components/
│   ├── common/
│   ├── forms/
│   ├── game/
│   ├── marketplace/
│   ├── wallet/
│   ├── profile/
│   └── organization/
├── hooks/
├── services/
├── api/
├── stores/
├── providers/
├── utils/
├── constants/
├── types/
├── navigation/
├── features/
├── assets/
├── theme/
└── lib/
```

---

# Feature Structure

Every feature owns its files.

Example

```
features/

Party/

components/

hooks/

api/

types/

screens/

utils/
```

Avoid dumping everything into global folders.

---

# Navigation

Use

Expo Router

Structure

```
(auth)

(tabs)

(modals)

(profile)

(wallet)

(party)

(admin)
```

---

# Screen Organization

Every screen contains

```
Screen

↓

Hooks

↓

Components

↓

API

↓

UI
```

Business logic should never live inside JSX.

---

# Components

Keep components

Small

Reusable

Focused

Single Responsibility

Preferred

<200 lines

Maximum

300 lines

---

# Component Types

Presentational

Smart

Layout

Shared

Modal

Bottom Sheet

Card

Form

---

# Example Structure

```
PartyCard/

PartyCard.tsx

PartyCard.types.ts

PartyCard.test.tsx
```

---

# State Management

React Query

Owns

API State

Server Data

Pagination

Cache

Invalidation

Never duplicate server state.

---

# Zustand

Owns

Theme

Authentication Session

Temporary UI

Selected Party

Bottom Sheets

Draft Messages

Filters

Never store API responses permanently.

---

# Forms

Use

React Hook Form

Validation

Zod

Never use

```
useState()
```

for large forms.

---

# API Layer

All API communication lives in

```
src/api
```

Never call

```
fetch()

axios()

```

inside components.

---

# API Structure

```
api/

party.api.ts

wallet.api.ts

marketplace.api.ts

profile.api.ts
```

---

# React Query

Every request has

Query Key

Example

```
['wallet']

['party', id]

['marketplace']

['notifications']
```

---

# Query Rules

Always

Cache

Invalidate

Refetch

Optimistic Updates

where appropriate.

---

# Mutations

Example Flow

```
Mutation

↓

Loading

↓

Success

↓

Invalidate Cache

↓

UI Update
```

---

# Error Handling

Every mutation handles

Loading

Success

Error

Retry

Offline

Retry only with

Idempotency Key

for financial mutations.

---

# Offline Support

Queue mutations.

Retry automatically.

Synchronize

After reconnect.

Exclude from blind offline queueing

Wallet

Purchases

Payments

Financial mutations require

Idempotency Key

Server Reconciliation

before retry or replay.

---

# Authentication

Use

Clerk SDK

Never store

JWT

Refresh Tokens

inside AsyncStorage.

Use

SecureStore

---

# Permissions

Centralize

Camera

Microphone

Notifications

Contacts

Location

Media Library

---

# Theming

Support

Light

Dark

System

Use design tokens.

Never hardcode colors.

---

# Styling

Use

NativeWind

Avoid

Inline styles

Unless dynamic.

---

# Typography

Use

Design System

```
Heading

Title

Body

Caption

Label
```

---

# Icons

Use

Lucide

or

Phosphor

Consistently.

---

# Images

Optimize

Lazy Load

Cache

CDN

---

# Lists

Always use

FlashList

for long lists.

Avoid

FlatList

when rendering large datasets.

---

# Animations

Use

Reanimated

Never block

JS Thread

---

# Gesture Handling

Use

Gesture Handler

Bottom Sheets

Cards

Swipe Actions

---

# Bottom Sheets

Use

Gorhom Bottom Sheet

Examples

Wallet

Party Actions

Marketplace

Filters

---

# Modals

Only for

Critical Decisions

Confirmation

Payment

Permissions

---

# Loading States

Every screen includes

Skeleton

Spinner

Refreshing

Pagination Loading

---

# Empty States

Every feature defines

Empty UI

Action

Illustration

CTA

---

# Error States

Display

Friendly Message

Retry Button

Support Link

---

# Realtime

Use

Laravel Reverb

Examples

Party Updates

Lobby

Leaderboard

Chat

Notifications

---

# Voice

Use

LiveKit

Never build

Voice infrastructure manually.

---

# AI

All AI calls go through

Backend API.

Never call

OpenAI

Anthropic

Gemini

directly from mobile.

---

# Notifications

Expo Notifications

Support

Push

Foreground

Background

Deep Linking

---

# Deep Links

Examples

```
yowimo://party/123

yowimo://wallet

yowimo://creator/42
```

---

# Accessibility

Support

VoiceOver

TalkBack

Dynamic Font Size

Reduced Motion

High Contrast

Large Touch Targets

---

# Localization

Never hardcode text.

Use

Translation files.

---

# Performance

Avoid

Anonymous Functions

Unnecessary Renders

Nested ScrollViews

Large Context Providers

---

# Memoization

Use

```
useMemo

useCallback

memo()
```

Only when beneficial.

---

# Images

Store

Remote URLs

Never

Base64

inside state.

---

# Security

Never store

Secrets

Payment Keys

AI Keys

JWT Secrets

inside application code.

---

# Logging

Use

Development Logger

Crash Reporting

Production

Minimal Logging

---

# Crash Reporting

Use

Sentry

Track

JS Errors

Native Crashes

Performance

---

# Testing

Every feature includes

Unit Tests

Component Tests

Integration Tests

---

# Naming

Screens

```
PartyScreen
```

Hooks

```
useParty
```

Components

```
PartyCard
```

Stores

```
useWalletStore
```

API

```
party.api.ts
```

---

# Hooks

Custom hooks own

Fetching

Mutations

Realtime

Business UI Logic

Example

```
useWallet()

useMarketplace()

useParty()

useNotifications()
```

---

# Environment

Never use

```
process.env

```

inside components.

Configuration belongs

Inside

Config Service.

---

# File Size Guidelines

Component

<200 lines

Hook

<150 lines

Screen

<300 lines

Store

<200 lines

---

# Code Style

TypeScript Strict Mode

ESLint

Prettier

No

```
any
```

unless unavoidable.

---

# Anti-Patterns

Never

Business Logic in Components

API Calls in JSX

Massive Screens

Deep Prop Drilling

Global Mutable State

Nested Ternaries

Magic Strings

---

# Feature Flags

Use

Remote Configuration

Never delete inactive code immediately.

---

# Analytics

Track

Screen Views

Button Clicks

Purchases

Party Creation

Errors

Performance

---

# Mobile Architecture

```text
UI

↓

Hooks

↓

API Layer

↓

Backend

↓

React Query

↓

UI Refresh
```

---

# Claude Code Instructions

When generating React Native code:

1. Use Expo Router.
2. Use React Query for server state.
3. Use Zustand for client state.
4. Keep components small and reusable.
5. Keep API calls outside components.
6. Follow the Design System.
7. Optimize for performance and accessibility.
8. Update this guide whenever architecture changes.

---

# Acceptance Criteria

The React Native implementation is complete when

✓ Navigation follows Expo Router.

✓ API communication is centralized.

✓ State ownership is clear.

✓ Components remain reusable.

✓ Offline support exists.

✓ Accessibility is implemented.

✓ Performance is optimized.

✓ Code follows platform standards.

---

# Mobile Development Workflow

```text
Requirement

↓

Screen Design

↓

Types

↓

API

↓

Hooks

↓

Components

↓

Navigation

↓

Realtime

↓

Testing

↓

Documentation
```

---

# Reference Architecture

```text
Expo Router

↓

Screen

↓

Custom Hook

↓

API Service

↓

Laravel API

↓

React Query

↓

UI Update
```

---

# Mobile Release Checklist

Before every release verify

✓ Authentication

✓ Navigation

✓ Deep Links

✓ Push Notifications

✓ Realtime

✓ Voice

✓ Offline Sync

✓ Marketplace

✓ Wallet

✓ Accessibility

✓ Performance

✓ Crash Reporting

---

# Future Mobile Features

```
Widgets

Wear OS

Apple Watch

CarPlay

Android Auto

Vision Pro

AR Experiences

Offline AI

Background Party Sync

Interactive Live Activities
```

---
