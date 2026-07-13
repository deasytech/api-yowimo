# Yowimo Admin Panel Implementation Guide

**Version:** 1.0.0

**Status:** Administrative Platform Engineering Standards

**Priority:** CRITICAL

**Owner:** Platform Engineering

**Framework**

Laravel 12

Filament v4

Livewire 3

Tailwind CSS

Alpine.js

PostgreSQL

Redis

Laravel Horizon

Laravel Reverb

---

# Purpose

This guide defines how every administrative feature inside Yowimo must be implemented.

The Admin Panel is mission-critical infrastructure.

It manages

- Users
- Wallets
- Marketplace
- Organizations
- AI
- Moderation
- Analytics
- Payments
- Infrastructure
- Platform Configuration

Every admin feature must follow these standards.

---

# Engineering Principles

Every admin feature must be

Secure

Auditable

Performant

Discoverable

Consistent

Permission Driven

Scalable

Recoverable

---

# Technology Stack

Framework

```
Laravel 12
```

Admin

```
Filament v4
```

Reactive UI

```
Livewire
```

Styling

```
TailwindCSS
```

Charts

```
Chart.js

ApexCharts
```

Realtime

```
Laravel Reverb
```

Authentication

```
Clerk
```

Authorization

```
Policies

Gates
```

---

# Project Structure

```
app/

Filament/

├── Resources/
├── Pages/
├── Widgets/
├── Clusters/
├── Tables/
├── Forms/
├── Actions/
├── Infolists/
├── Exports/
├── Imports/
└── Support/
```

---

# Resource Organization

Every business domain owns its own resource.

```
Users

Wallets

Marketplace

Organizations

Creator

AI

Moderation

Payments

Analytics

Infrastructure
```

Avoid generic resource folders.

---

# Admin Navigation

```
Dashboard

↓

Operations

↓

Community

↓

Commerce

↓

Enterprise

↓

AI

↓

Reports

↓

Infrastructure

↓

Configuration

↓

Audit
```

---

# Navigation Groups

```
Dashboard

Users

Parties

Games

Wallet

Marketplace

Creators

Organizations

Moderation

Notifications

Payments

Analytics

Infrastructure

System

Security
```

---

# Dashboard

Must contain

Platform Status

Realtime Metrics

Revenue

Active Users

Current Parties

Active Organizations

Marketplace Sales

Queue Health

Server Health

---

# Widgets

Widgets must remain

Focused

Realtime

Cached

Reusable

---

# Dashboard Widgets

```
Revenue Widget

Online Users

Current Parties

Queue Health

Redis Status

Database Health

API Latency

Top Games

Top Creators

Recent Payments
```

---

# Resources

Each Filament Resource contains

```
List

View

Create

Edit

Delete

Bulk Actions

Filters

Exports
```

---

# Forms

Use

Filament Forms

Never manually build forms unless necessary.

Validation belongs inside

Form Components

and

Laravel Requests

---

# Tables

Every table supports

Pagination

Search

Sorting

Filtering

Column Toggle

Export

Bulk Actions

---

# Standard Filters

Created Date

Updated Date

Status

Organization

Country

Creator

Role

Verification

---

# Standard Actions

View

Edit

Delete

Duplicate

Export

Audit History

Impersonate (Admins Only)

---

# Bulk Actions

Delete

Suspend

Approve

Reject

Assign

Export

Notify

---

# Users Module

Capabilities

```
Search Users

View Profile

Suspend

Ban

Reset Sessions

Reset MFA

View Wallet

View Purchases

View Audit Log
```

---

# Wallet Module

Capabilities

```
View Wallet

Ledger

Transactions

Rewards

Purchases

Refunds

Reconciliation
```

Never edit balances directly.

---

# Marketplace Module

Manage

Products

Purchases

Categories

Featured Packs

Reviews

Revenue

Creator Reports

---

# Creator Module

Manage

Applications

Verification

Revenue

Payouts

Statistics

Featured Status

---

# Organization Module

Manage

Organizations

Departments

Employees

Subscriptions

Branding

Usage

---

# Moderation Module

Manage

Reports

Appeals

Content Review

Chat Review

Card Packs

User Actions

---

# AI Module

Manage

Prompt Registry

Providers

Fallback Chains

Costs

Usage

Latency

Moderation

---

# Payment Module

Manage

Transactions

Refunds

Webhooks

Payment Providers

Settlement Reports

---

# Analytics Module

Reports

DAU

MAU

Retention

Revenue

Marketplace

Creators

Organizations

Countries

Games

---

# Infrastructure Module

View

Queues

Redis

Database

Workers

Reverb

LiveKit

Health Checks

---

# Configuration Module

Manage

Feature Flags

Business Rules

Limits

AI Settings

Marketplace Settings

Localization

---

# Audit Module

Every sensitive action records

Actor

Target

Timestamp

IP

Browser

Old Value

New Value

Reason

---

# Search

Global Search

Supports

Users

Organizations

Creators

Parties

Products

Payments

Reports

---

# Permissions

Every resource protected by

Policies

Permissions

Role Checks

Tenant Validation

---

# Multi-Tenant Rules

Organization Admins

Only access

Their Organization

Platform Admins

Access Everything

---

# Exporting

Supported Formats

```
CSV

Excel

PDF
```

Large exports

Queued

---

# Imports

Supported

Users

Organizations

Employees

Marketplace Content

Creator Packs

All imports

Validated

Queued

Audited

---

# Charts

Use

Cached Queries

Never query millions of rows synchronously.

---

# Notifications

Admin Notifications

Security Alerts

Payment Failures

Queue Failures

AI Failures

Moderation Alerts

Infrastructure Alerts

---

# Realtime

Update

Dashboard

Queues

Revenue

Active Parties

Server Health

Using

Laravel Reverb

---

# Performance

Cache

Statistics

Charts

Leaderboards

Aggregations

Avoid

N+1 Queries

---

# Dangerous Actions

Require

Confirmation

Reason

Permission

Audit Entry

Examples

Delete User

Refund

Suspend Creator

Ban User

Delete Organization

---

# Impersonation

Admins may impersonate users.

Requirements

Permission

Audit

Visible Banner

Exit Button

No financial actions while impersonating.

---

# File Uploads

Validate

MIME

Size

Virus Scan

Store

S3

---

# Security

Never expose

Secrets

Environment Variables

Payment Keys

JWT Tokens

Private Messages

---

# Logging

Every admin action logs

Actor

Action

Resource

Changes

Result

Duration

---

# Code Organization

Each resource

```
Resource

Pages

Widgets

Tables

Forms

Policies

Services
```

---

# Testing

Every resource includes

Feature Tests

Policy Tests

Permission Tests

Bulk Action Tests

Export Tests

---

# Accessibility

Support

Keyboard Navigation

Screen Readers

High Contrast

Large Fonts

---

# Mobile Support

Admin Panel

Tablet Optimized

Desktop First

Phone Support

Limited

---

# File Naming

```
UserResource

WalletResource

CreatorResource

MarketplaceResource

OrganizationResource
```

---

# Anti Patterns

Never

Business Logic in Resources

Raw SQL in Pages

Permission Checks in Views

Large Widgets

Uncached Analytics

---

# Admin Workflow

```text
User Action

↓

Permission

↓

Policy

↓

Service

↓

Repository

↓

Database

↓

Audit

↓

Realtime

↓

Notification
```

---

# Claude Code Instructions

When generating admin functionality:

1. Use Filament Resources.
2. Keep business logic in services.
3. Protect every action with permissions.
4. Audit sensitive actions.
5. Queue expensive operations.
6. Cache dashboard statistics.
7. Keep widgets focused.
8. Update this guide whenever new admin functionality is introduced.

---

# Acceptance Criteria

The Admin Panel implementation is complete when

✓ Every domain has a resource.

✓ Permissions protect every action.

✓ Sensitive actions are audited.

✓ Dashboard updates in realtime.

✓ Analytics are cached.

✓ Bulk actions are supported.

✓ Large exports are queued.

✓ Platform administration follows consistent patterns.

---

# Admin Development Workflow

```text
Requirement

↓

Permission

↓

Resource

↓

Form

↓

Table

↓

Service

↓

Repository

↓

Audit

↓

Tests

↓

Documentation
```

---

# Reference Architecture

```text
Filament Resource

↓

Policy

↓

Service

↓

Repository

↓

Database

↓

Audit Log

↓

Realtime Dashboard
```

---

# Future Admin Features

```
AI Admin Assistant

Platform Health Center

Revenue Forecasting

Fraud Investigation Console

Developer Portal

Plugin Marketplace

Workflow Automation

Custom Dashboards

SOC2 Compliance Center

Incident Command Center
```

---
