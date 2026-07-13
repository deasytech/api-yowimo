# Yowimo Multi-Tenant Enterprise Architecture

**Version:** 1.0.0

**Status:** Enterprise Infrastructure Specification

**Priority:** CRITICAL

**Owner:** Platform Architecture Team

**Depends On**

- 02_SYSTEM_ARCHITECTURE.md
- 16_ADMIN_PANEL_ARCHITECTURE.md
- 18_INFRASTRUCTURE_AND_DEVOPS.md
- 28_CORPORATE_PLATFORM_ARCHITECTURE.md
- 29_CREATOR_ECONOMY_AND_MARKETPLACE.md

---

# Purpose

Yowimo is designed to serve multiple customers from a single platform.

Customers include

- Individual Players
- Companies
- Universities
- Event Organizers
- Creator Communities
- White-label Partners

Each customer must operate independently while sharing the same platform infrastructure.

---

# Philosophy

Every tenant should feel like they own their own platform.

But operationally, Yowimo remains a single application.

---

# Multi-Tenant Architecture

```text
Platform

↓

Tenant

↓

Workspace

↓

Organization

↓

Departments

↓

Users

↓

Parties

↓

Analytics
```

---

# Tenant Definition

A Tenant represents an isolated customer.

Examples

Netflix Nigeria

Google Africa

University of Lagos

Tech Conference 2027

Creator Community

Enterprise Client

---

# Tenant Entity

Fields

```
id

name

slug

type

status

country

timezone

subscription_plan

branding

settings

created_at
```

---

# Tenant Types

Consumer

Corporate

Education

Creator

Enterprise

White Label

Government (Future)

---

# Tenant Isolation

Every tenant owns

Users

Settings

Analytics

Marketplace

Content

Billing

Reports

Roles

Storage References

---

No tenant can access another tenant's data.

---

# Isolation Strategy

Application-Level Isolation

Primary

Future

Database-per-Tenant (Enterprise)

---

# Query Rules

Every database query must include

```
tenant_id
```

Example

```php
Party::where('tenant_id', $tenantId)
```

Never query tenant-owned data without tenant context.

---

# Tenant Resolution

Tenant determined from

Subdomain

Domain

Organization

JWT Claims

API Key

---

# Custom Domains

Supported

```
play.company.com

events.organization.org

games.university.edu
```

---

# White Label Domains

Examples

```
games.company.com

play.brand.com
```

SSL certificates managed automatically.

---

# Branding

Each tenant customizes

Logo

Primary Color

Secondary Color

Fonts (Future)

Splash Screen

Email Templates

Notifications

Event Themes

---

# Feature Flags

Tenant-specific features

Examples

Corporate Mode

Creator Marketplace

AI Host

Voice Chat

Video Rooms

Tournament Mode

Custom Branding

---

# Subscription Plans

Free

Starter

Professional

Business

Enterprise

Custom

---

# Usage Limits

Track

Users

Storage

Events

Voice Minutes

Video Minutes

AI Tokens

Marketplace Sales

API Requests

---

# Billing

Tenant Billing

Monthly

Annual

Usage-Based

Seat-Based

Hybrid

---

# Quotas

Examples

```
100 Employees

↓

100GB Storage

↓

10 Events

↓

Unlimited AI
```

Quotas enforced automatically.

---

# Roles

Platform Roles

↓

Tenant Roles

↓

Workspace Roles

↓

Department Roles

↓

User Roles

---

# Tenant Administrators

Manage

Users

Billing

Branding

Settings

Analytics

Marketplace

Integrations

---

# Enterprise API

Each tenant receives

API Keys

Webhook Secrets

Usage Metrics

Rate Limits

---

# Webhooks

Supported Events

```
party.started

party.completed

purchase.completed

employee.joined

reward.granted

user.created
```

---

# Integrations

Google Workspace

Microsoft 365

Slack

Zoom

Teams

Jira

Notion

Future ERP Systems

---

# Storage

Logical separation

```
tenant_id/

uploads/

images/

videos/

documents/
```

---

# Analytics

Every tenant receives isolated dashboards.

Metrics

Active Users

Events

Revenue

Participation

Training

Marketplace

AI Usage

---

# Search

Tenant search never returns

Other Tenant Data

---

# Security

Tenant Isolation enforced by

Middleware

Policies

Repositories

Queries

Caching

Storage

Broadcast Channels

---

# Caching

Cache Keys

```
tenant:{id}:...

```

Never share cache across tenants.

---

# Queue Jobs

Every queued job includes

```
tenant_id
```

Tenant context restored before execution.

---

# Notifications

Templates may be customized per tenant.

Brand

Colors

Logo

Language

Tone

---

# Marketplace

Each tenant may have

Public Products

Private Products

Organization Packs

Creator Packs

Sponsored Packs

---

# AI

Tenant-specific

Prompts

Brand Voice

Language

Policies

Knowledge Base (Future)

---

# Compliance

Support

GDPR

LGPD

CCPA

NDPA

SOC2 (Future)

ISO 27001 (Future)

---

# Audit Logs

Store

Tenant

User

Action

Timestamp

IP

Changes

Reason

---

# Backups

Backups tagged by

Tenant

Date

Region

Retention Policy

---

# Disaster Recovery

Restore

Single Tenant

Entire Platform

Specific Workspace

Without affecting other tenants.

---

# Data Residency (Future)

Support regional hosting.

Examples

Europe

United States

Brazil

Nigeria

Middle East

Asia

---

# Migration

Support

Tenant Export

Tenant Import

Tenant Merge

Tenant Split

---

# Lifecycle

Tenant Created

↓

Provisioned

↓

Configured

↓

Active

↓

Suspended

↓

Archived

↓

Deleted

---

# Monitoring

Track

Storage

Revenue

API Usage

AI Usage

Active Users

Errors

Security Events

---

# Future Features

Dedicated Databases

Dedicated Clusters

Private AI Models

Private Storage

Enterprise Search

Dedicated LiveKit

Dedicated Reverb

Enterprise Plugins

---

# Claude Code Instructions

When implementing multi-tenancy:

1. Every tenant-owned model includes tenant_id.
2. Resolve tenant before executing business logic.
3. Never cache data without tenant context.
4. Scope queues, storage, analytics, and notifications by tenant.
5. Support tenant branding through configuration.
6. Keep enterprise APIs tenant-aware.
7. Prevent all cross-tenant access.
8. Update this document whenever tenant capabilities expand.

---

# Acceptance Criteria

The Multi-Tenant Architecture is complete when:

- Tenant data is fully isolated.
- Branding is customizable.
- Billing and quotas are tenant-aware.
- APIs, storage, queues, and analytics are tenant-scoped.
- White-label deployments are supported.
- Enterprise customers can operate independently on shared infrastructure.

---
