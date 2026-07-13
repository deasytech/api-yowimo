# Yowimo Permission Matrix

**Version:** 1.0.0

**Status:** Authorization & Access Control Specification

**Priority:** CRITICAL

**Owner:** Security Team / Backend Platform Team

**Authorization Model**

RBAC (Role-Based Access Control)

ABAC (Attribute-Based Access Control)

Laravel Policies

Laravel Gates

Multi-Tenant Aware

**Depends On**

- 06_AUTHENTICATION_AND_AUTHORIZATION.md
- 16_ADMIN_PANEL_ARCHITECTURE.md
- 28_CORPORATE_PLATFORM_ARCHITECTURE.md
- 29_CREATOR_ECONOMY_AND_MARKETPLACE.md
- 30_MULTI_TENANT_ENTERPRISE_ARCHITECTURE.md

---

# Purpose

This document defines every permission inside Yowimo.

It specifies

- Roles
- Permissions
- Ownership Rules
- Tenant Isolation
- Corporate Roles
- Creator Roles
- Admin Permissions
- API Authorization
- Frontend Visibility

No protected resource should exist without a documented permission.

---

# Security Philosophy

Authentication answers

> Who are you?

Authorization answers

> What are you allowed to do?

Every request must pass both.

---

# Authorization Layers

```text
Authentication

↓

Tenant Validation

↓

Role Validation

↓

Permission Check

↓

Ownership Validation

↓

Business Rules

↓

Action Allowed
```

---

# Authorization Strategy

Primary

RBAC

Additional

ABAC

Ownership

Resource Policies

Enterprise

Tenant Policies

---

# Global Roles

```
Guest

User

Premium User

Creator

Verified Creator

Corporate Employee

Corporate Manager

Corporate Admin

Organization Owner

Moderator

Support Agent

Administrator

Super Administrator

System
```

---

# Permission Naming

Convention

```
resource.action
```

Examples

```
party.create

party.update

wallet.view

wallet.credit

marketplace.publish

creator.withdraw

admin.users.view

organization.invite
```

---

# Permission Groups

Identity

Users

Friends

Parties

Games

Wallet

Marketplace

Creator

Organization

Moderation

Notifications

Analytics

Admin

Infrastructure

AI

Sponsors

---

# GUEST

Can

✓ Register

✓ Login

✓ Browse Landing Pages

✓ View Public Marketplace

Cannot

✗ Join Parties

✗ Send Messages

✗ Purchase Items

✗ Use Wallet

---

# USER

Can

✓ Manage Profile

✓ Join Parties

✓ Create Parties

✓ Invite Friends

✓ Send Messages

✓ Purchase Tokens

✓ Purchase Card Packs

✓ Report Content

✓ Earn Rewards

Cannot

✗ Moderate Users

✗ Publish Marketplace Content

✗ Access Admin

---

# PREMIUM USER

Everything User can do

Plus

✓ Premium Card Packs

✓ Exclusive Themes

✓ AI Premium Features

✓ Unlimited Favorites

✓ Advanced Statistics

---

# CREATOR

Everything Premium User can do

Plus

✓ Submit Card Packs

✓ Publish Marketplace Content

✓ View Creator Dashboard

✓ View Creator Revenue

✓ Request Payout

✓ Manage Creator Profile

Cannot

✗ Approve Own Content

✗ Access Other Creators' Revenue

---

# VERIFIED CREATOR

Everything Creator can do

Plus

✓ Featured Listings

✓ Priority Publishing

✓ Verified Badge

✓ Early Access Features

---

# CORPORATE EMPLOYEE

Can

✓ Join Organization Events

✓ Participate in Training

✓ View Organization Leaderboards

✓ Earn Organization Rewards

Cannot

✗ Manage Organization

---

# CORPORATE MANAGER

Can

✓ Create Corporate Events

✓ Invite Employees

✓ View Team Reports

✓ Moderate Team Activities

✓ Generate Team Reports

---

# CORPORATE ADMIN

Can

✓ Manage Departments

✓ Manage Teams

✓ View Organization Analytics

✓ Configure Organization

✓ Assign Roles

✓ Manage Branding

---

# ORGANIZATION OWNER

Full control over

Organization

Billing

Users

Branding

Subscription

Marketplace

Corporate Content

Audit Logs

---

# MODERATOR

Can

✓ Review Reports

✓ Suspend Users

✓ Remove Content

✓ Lock Parties

✓ Review Appeals

Cannot

✗ View Wallet Balances

✗ Modify Payments

---

# SUPPORT AGENT

Can

✓ View Support Tickets

✓ Reset Sessions

✓ View User Profiles

✓ Issue Refund Requests

Cannot

✗ Modify Wallet

✗ Modify Database

✗ Access Secrets

---

# ADMINISTRATOR

Can

Everything except

Infrastructure

Secrets

System Configuration

---

# SUPER ADMINISTRATOR

Full platform access.

Restricted to trusted internal staff.

Requires

MFA

Hardware Key (Future)

Audit Logging

---

# SYSTEM

Internal automation only.

Used by

Queues

Schedulers

Webhooks

AI

Never assigned to humans.

---

# RESOURCE PERMISSIONS

---

# USERS

```
user.view

user.update

user.delete

user.block

user.report

user.export
```

Ownership

Users manage only themselves.

Admins may manage others.

---

# FRIENDS

```
friend.request

friend.accept

friend.remove

friend.block

friend.unblock
```

---

# PARTIES

```
party.create

party.view

party.update

party.delete

party.start

party.end

party.invite

party.kick

party.join

party.leave
```

Ownership

Only Host

or

Assigned Moderator

may modify party.

---

# GAMEPLAY

```
game.start

game.pause

game.resume

game.vote

game.skip

game.complete

game.reaction
```

---

# CHAT

```
chat.send

chat.edit

chat.delete

chat.react

chat.moderate
```

Users edit only

Own Messages.

---

# WALLET

```
wallet.view

wallet.purchase

wallet.transfer

wallet.withdraw

wallet.credit

wallet.debit

wallet.refund
```

Restrictions

Users

Cannot

Credit

Debit

Refund

Those require

System

or

Admin

---

# MARKETPLACE

```
marketplace.view

marketplace.purchase

marketplace.publish

marketplace.update

marketplace.archive
```

Creators

Own Content Only.

---

# INVENTORY

```
inventory.view

inventory.use

inventory.transfer
```

Transfers

Future Feature.

---

# CREATOR

```
creator.apply

creator.publish

creator.edit

creator.analytics

creator.withdraw

creator.delete
```

---

# ORGANIZATION

```
organization.create

organization.update

organization.delete

organization.invite

organization.analytics

organization.billing

organization.branding
```

---

# DEPARTMENTS

```
department.create

department.update

department.delete
```

---

# EMPLOYEES

```
employee.invite

employee.remove

employee.promote

employee.demote
```

---

# MODERATION

```
moderation.review

moderation.suspend

moderation.ban

moderation.restore

moderation.appeal
```

---

# ANALYTICS

```
analytics.personal

analytics.creator

analytics.organization

analytics.admin

analytics.platform
```

---

# NOTIFICATIONS

```
notification.send

notification.read

notification.delete
```

---

# FILES

```
upload.create

upload.delete

upload.view
```

Ownership enforced.

---

# AI

```
ai.host

ai.translate

ai.summary

ai.recommend

ai.moderate
```

Admin Only

```
ai.configuration

ai.prompts

ai.providers
```

---

# SPONSORS

```
sponsor.create

sponsor.edit

sponsor.analytics

campaign.publish

campaign.archive
```

---

# ADMIN

```
admin.dashboard

admin.users

admin.wallet

admin.marketplace

admin.analytics

admin.configuration

admin.audit

admin.roles
```

---

# INFRASTRUCTURE

Restricted

DevOps

Only

```
servers.manage

queues.manage

redis.manage

deployment.execute

logs.view

feature_flags.manage
```

---

# API AUTHORIZATION

Every endpoint documents

Required Permission

Example

```
POST /parties

Permission

party.create
```

---

# FRONTEND AUTHORIZATION

Hide UI

When permission missing.

Never rely only on frontend.

Backend always validates.

---

# TENANT ISOLATION

Users may access only

Their Tenant

Organization

Workspace

Resources

Cross-tenant access

Forbidden.

---

# OWNERSHIP RULES

Users own

Profile

Messages

Parties

Creator Packs

Marketplace Listings

Organizations own

Corporate Assets

Corporate Reports

Training Data

---

# POLICY CLASSES

Laravel Policies

```
PartyPolicy

WalletPolicy

CreatorPolicy

OrganizationPolicy

MarketplacePolicy

NotificationPolicy

UserPolicy
```

---

# GATES

Global Gates

```
isAdmin

isModerator

isCreator

isOrganizationOwner

isSupportAgent
```

---

# AUDIT

Permission changes log

Actor

Permission

Target

Timestamp

Tenant

Reason

---

# PERMISSION CACHE

Cache

Roles

Permissions

Policy Results

Invalidate

Immediately

after updates.

---

# SECURITY RULES

Never trust

Frontend

JWT Claims Alone

Client Role Values

Always validate

Server-side.

---

# FUTURE PERMISSIONS

```
guild.manage

tournament.create

achievement.manage

season.manage

plugin.publish

developer.api

public_api.manage

live_event.host

ar.session.host

vr.room.host
```

---

# Claude Code Instructions

When implementing authorization:

1. Every protected resource requires a permission.
2. Use Laravel Policies for resource ownership.
3. Use Gates for global permissions.
4. Validate tenant access first.
5. Never trust frontend authorization.
6. Cache permissions safely.
7. Log permission changes.
8. Update this matrix whenever permissions change.

---

# Acceptance Criteria

The Permission Matrix is complete when:

- Every role is defined.
- Every permission is documented.
- API authorization is standardized.
- Ownership rules are enforced.
- Tenant isolation is guaranteed.
- Admin access is auditable.
- Developers can determine authorization without reading code.

---
