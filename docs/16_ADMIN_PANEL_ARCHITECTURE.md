# Yowimo Admin Panel Architecture

**Version:** 1.0.0

**Status:** Core Platform Specification

**Priority:** CRITICAL

**Owner:** Platform Operations Team

**Frontend:** Filament 4

**Backend:** Laravel 12

**Depends On**

- 03_DOMAIN_MODEL.md
- 04_DATABASE_ARCHITECTURE.md
- 05_API_STANDARDS.md
- 06_SECURITY_STANDARDS.md
- 07_EVENT_CATALOG.md
- 12_WALLET_AND_TOKEN_SYSTEM.md
- 13_MARKETPLACE_ARCHITECTURE.md
- 15_ANALYTICS_AND_OBSERVABILITY.md

---

# Purpose

The Admin Panel is the operational headquarters of Yowimo.

Everything that happens inside the platform should be manageable through the Admin Panel.

No administrator should ever need direct database access.

---

# Core Principles

The Admin Panel must be

✓ Secure

✓ Fast

✓ Auditable

✓ Modular

✓ Permission Based

✓ Mobile Friendly

✓ Extensible

---

# Technology

Frontend

Filament 4

Backend

Laravel 12

Authentication

Clerk

Authorization

Laravel Policies

Spatie Permissions

---

# Architecture

```text
Administrator

↓

Filament

↓

Resources

↓

Services

↓

Repositories

↓

Database
```

The Admin Panel never performs business logic directly.

Business logic belongs inside services.

---

# Admin Modules

Dashboard

Users

Parties

Games

Cards

Marketplace

Wallet

Sponsors

Analytics

Notifications

AI

Reports

Support

System Settings

Audit Logs

Feature Flags

---

# Dashboard

The Dashboard is the first screen after login.

Widgets

Today's Users

Active Parties

Online Players

Revenue Today

Tokens Issued

Marketplace Sales

Open Support Tickets

System Health

Queue Status

LiveKit Status

Reverb Status

---

# User Management

Administrators can

View Users

Search Users

Suspend Users

Reactivate Users

Reset Verification

Adjust Roles

View Wallet

View Statistics

View Devices

View Sessions

View Audit Logs

Administrators cannot impersonate users without permission.

---

# User Details

Show

Profile

Statistics

Friends

Wallet

Purchases

Achievements

Reports

Violations

Devices

Recent Activity

---

# Party Management

Administrators can

View Parties

Search Parties

Terminate Party

Pause Party

Archive Party

Review Reports

View Participants

View Chat

View Reactions

---

# Game Management

Manage

Game Types

Difficulty

Rules

Timers

Availability

Age Ratings

Visibility

Featured Games

---

# Card Management

Manage

Truth Cards

Dare Cards

Trivia

Never Have I Ever

Corporate Packs

Spicy Packs

Family Packs

Seasonal Packs

AI Generated Cards

---

Each card supports

Difficulty

Age Rating

Category

Language

Status

Tags

Artwork

Version

---

# Marketplace Management

Manage

Products

Bundles

Pricing

Discounts

Coupons

Themes

Voice Packs

Seasonal Items

Creator Packs

---

Administrators can

Publish

Unpublish

Archive

Feature

Schedule

Duplicate

Products

---

# Wallet Management

Administrators can

Search Wallets

View Ledger

View Transactions

Issue Rewards

Issue Refunds

Adjust Balance

Freeze Wallet

View Fraud Flags

All actions require audit logs.

---

# Sponsor Management

Manage

Sponsor Accounts

Campaigns

Sponsored Parties

Credits

Budgets

Invoices

Reports

Brand Assets

---

# Notification Management

Manage

Templates

Campaigns

Push Notifications

Email Templates

Localized Content

Scheduling

Delivery Reports

---

# AI Management

Manage

Prompt Versions

Voice Packs

AI Personalities

Provider Selection

Moderation Rules

AI Cost Reports

Fallback Providers

---

# Analytics

View

Revenue

Retention

Growth

Marketplace

Wallet

Sponsors

Notifications

AI Usage

Gameplay

Infrastructure

---

# Reports

Generate

Financial Reports

User Reports

Growth Reports

Marketplace Reports

Corporate Reports

Sponsor Reports

Moderation Reports

Audit Reports

---

Reports support

CSV

Excel

PDF

---

# Customer Support

Support Agents can

Search Users

View Tickets

Respond

Escalate

Resolve

Issue Credits

Reset Accounts

View History

---

# Moderation

Moderators manage

Reported Users

Reported Messages

Reported Highlights

Blocked Accounts

Muted Users

Appeals

Moderation Queue

---

# Content Management

Manage

Homepage Banners

Announcements

Events

Featured Packs

News

Maintenance Messages

Release Notes

---

# Feature Flags

Enable

Disable

Features

Examples

AI Host

Corporate Mode

Creator Marketplace

Tournament Mode

Video Recording

Battle Pass

Rollout percentages supported.

---

# System Settings

Manage

Currencies

Token Values

Signup Rewards

Referral Rewards

Party Limits

Upload Limits

Storage Providers

Email Providers

AI Providers

LiveKit

Reverb

Redis

---

# Roles

Super Admin

Administrator

Finance

Support

Moderator

Marketing

Content Editor

Sponsor Manager

Developer

Read Only

---

# Permissions

Examples

```
users.view

users.update

wallet.credit

wallet.refund

cards.publish

marketplace.publish

reports.view

settings.update
```

Permissions are granular.

---

# Audit Logs

Every administrative action records

Admin ID

Action

Entity

Old Values

New Values

IP Address

Timestamp

Reason

Nothing is excluded.

---

# Search

Global Search supports

Users

Parties

Products

Cards

Wallets

Transactions

Reports

Sponsors

Tickets

---

# Bulk Actions

Supported

Publish Products

Suspend Users

Issue Rewards

Archive Parties

Export Reports

Delete Drafts

Every bulk action requires confirmation.

---

# File Management

Manage

Images

Videos

Card Artwork

Voice Files

Marketing Assets

Sponsor Logos

Documents

Supports versioning.

---

# System Monitoring

Display

CPU

Memory

Queue Health

Redis

Database

Storage

LiveKit

Reverb

AI Providers

---

# Alerts

Administrators receive alerts for

Payment Failures

Queue Failures

Storage Issues

Security Events

Provider Downtime

Fraud Detection

High Error Rates

---

# Security

Admin Panel requires

MFA

Strong Password Policy

Session Timeout

IP Logging

Audit Logging

Permission Checks

Sensitive actions require confirmation.

---

# API Access

Future

Admin API

Supports

Internal Dashboards

Partner Systems

Corporate Integrations

---

# Future Features

Organization Management

White Label Management

Multi-Tenant Administration

Creator Portal

Partner Portal

Franchise Management

Marketplace Approvals

Subscription Management

---

# Claude Code Instructions

When implementing the Admin Panel:

1. Use Filament Resources wherever appropriate.
2. Keep business logic inside Services.
3. Protect every resource with policies.
4. Log every administrative action.
5. Use Spatie Permissions for RBAC.
6. Never expose raw database operations.
7. Support bulk actions safely.
8. Keep dashboards performant with caching.
9. Update this document whenever new admin capabilities are introduced.

---

# Acceptance Criteria

The Admin Panel is complete when:

- Every major domain entity has a dedicated resource.
- All actions are permission protected.
- Audit logs capture every administrative operation.
- Dashboards provide real-time operational visibility.
- Customer support can resolve common issues without database access.
- Marketplace, Wallet, AI, Sponsors, and Analytics are fully manageable.
- The platform can scale operationally without code changes.

---
