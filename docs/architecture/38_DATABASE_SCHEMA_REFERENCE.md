# Yowimo Database Schema Reference

**Version:** 1.0.0

**Status:** Database Engineering Specification

**Priority:** CRITICAL

**Owner:** Backend Platform Team

**Database**

PostgreSQL 16+

**ORM**

Laravel Eloquent

**Depends On**

- 03_DOMAIN_MODEL.md
- 04_DATABASE_ARCHITECTURE.md
- 12_WALLET_AND_TOKEN_SYSTEM.md
- 13_MARKETPLACE_ARCHITECTURE.md
- 28_CORPORATE_PLATFORM_ARCHITECTURE.md
- 30_MULTI_TENANT_ENTERPRISE_ARCHITECTURE.md

---

# Purpose

This document is the single source of truth for the Yowimo database.

It documents

- Every table
- Every column
- Every relationship
- Every foreign key
- Every index
- Every constraint
- Every enum
- Every partition
- Every audit rule

Developers should never guess database structures.

---

# Database Principles

✓ UUID Primary Keys

✓ Immutable Financial Records

✓ Soft Deletes

✓ Audit Logging

✓ Multi-Tenant

✓ Optimized Indexes

✓ Referential Integrity

✓ Event-Friendly Design

---

# Naming Convention

Tables

Plural

Examples

```
users

parties

wallet_transactions

notifications
```

---

Columns

snake_case

Examples

```
first_name

created_at

updated_at

deleted_at
```

---

Foreign Keys

```
user_id

party_id

wallet_id

organization_id
```

---

Pivot Tables

Alphabetical

Example

```
party_user

game_player

role_permission
```

---

# Primary Keys

Every table uses

UUID

Example

```sql
id UUID PRIMARY KEY
```

Never use auto increment IDs.

---

# Timestamp Convention

Every table includes

```sql
created_at

updated_at
```

Soft deletable tables include

```sql
deleted_at
```

---

# Multi-Tenant Rule

Every tenant-owned table includes

```sql
tenant_id UUID
```

Indexed.

Required.

---

# Audit Rule

Sensitive tables include

```sql
created_by

updated_by

deleted_by
```

---

# Table Categories

Core

Identity

Social

Gaming

Wallet

Marketplace

Creator

Enterprise

Analytics

Infrastructure

---

# CORE TABLES

---

## users

Purpose

Platform users.

Columns

```
id

tenant_id

clerk_id

username

display_name

email

phone

avatar

bio

country

language

timezone

date_of_birth

gender

status

last_seen_at

email_verified_at

phone_verified_at

created_at

updated_at

deleted_at
```

Indexes

```
clerk_id

email

username

tenant_id

status

last_seen_at
```

Relationships

```
hasOne Wallet

hasMany Devices

hasMany Friends

hasMany Notifications

hasMany Parties

hasMany Purchases
```

---

## user_profiles

Stores extended profile information.

Columns

```
id

user_id

occupation

interests

favorite_games

social_links

preferences

metadata
```

---

## devices

Stores registered devices.

Columns

```
id

user_id

device_id

platform

push_token

app_version

os_version

last_active_at
```

---

# FRIEND SYSTEM

---

## friendships

Columns

```
id

sender_id

receiver_id

status

accepted_at

created_at
```

Status

Pending

Accepted

Blocked

Rejected

Indexes

```
sender_id

receiver_id

status
```

---

## blocked_users

Stores blocked relationships.

---

# PARTY SYSTEM

---

## parties

Columns

```
id

tenant_id

host_id

game_id

title

description

party_type

visibility

status

max_players

scheduled_at

started_at

ended_at

settings

created_at

updated_at
```

Indexes

```
host_id

tenant_id

status

scheduled_at
```

---

## party_players

Pivot

```
id

party_id

user_id

joined_at

left_at

score

ready

team_id

seat_number
```

---

## party_invitations

Stores invitations.

---

## party_reactions

Stores live emoji reactions.

---

## party_presence

Stores realtime presence.

---

# GAME SYSTEM

---

## games

Master game list.

Examples

Truth

Dare

Never Have I Ever

Trivia

Icebreaker

---

Columns

```
id

slug

title

description

status

icon

cover

difficulty

created_at
```

---

## game_modes

Examples

Classic

Party

Corporate

Couples

---

## game_rounds

Stores rounds.

---

## turns

Stores player turns.

---

## game_sessions

Stores completed sessions.

---

## player_scores

Stores cumulative scoring.

---

# CONTENT

---

## card_packs

Marketplace-ready packs.

---

## cards

Columns

```
id

pack_id

category

difficulty

age_rating

language

title

prompt

metadata

status

version
```

Indexes

```
pack_id

category

difficulty

language

status
```

---

## card_versions

Immutable history.

---

## card_tags

Master tags.

---

## card_tag

Pivot.

---

# WALLET

---

## wallets

One per user.

Columns

```
id

user_id

balance

lifetime_earned

lifetime_spent

status
```

---

## wallet_transactions

Ledger table.

Columns

```
id

wallet_id

type

amount

balance_before

balance_after

reference

reference_type

status

metadata

created_at
```

Indexes

```
wallet_id

reference

type

created_at
```

Immutable.

Never updated.

---

## token_packages

Store purchasable token bundles.

---

## wallet_rewards

Stores reward history.

---

# MARKETPLACE

---

## marketplace_items

Products.

---

## purchases

Completed purchases.

---

## purchase_items

Purchase details.

---

## inventory

Player owned assets.

---

## creator_revenue

Revenue sharing.

---

# CREATOR

---

## creators

Creator profiles.

---

## creator_followers

Follower relationships.

---

## creator_payouts

Payout history.

---

## creator_statistics

Analytics.

---

# ORGANIZATIONS

---

## organizations

Enterprise organizations.

---

## workspaces

---

## departments

---

## employees

---

## organization_roles

---

## organization_invitations

---

# CHAT

---

## conversations

---

## conversation_participants

---

## messages

Columns

```
id

conversation_id

sender_id

type

content

attachments

edited_at

deleted_at
```

---

## message_reactions

---

## message_reads

---

# NOTIFICATIONS

---

## notifications

Columns

```
id

user_id

title

body

type

read_at

metadata

created_at
```

---

## notification_preferences

---

# AI

---

## ai_prompts

Prompt registry.

---

## ai_prompt_versions

---

## ai_requests

Logs requests.

---

## ai_responses

Logs responses.

---

# ANALYTICS

---

## analytics_events

Partitioned.

Columns

```
id

tenant_id

user_id

event

payload

ip

device

country

created_at
```

---

## user_metrics

---

## daily_metrics

---

## leaderboard_snapshots

---

# MODERATION

---

## reports

---

## moderation_actions

---

## bans

---

## appeals

---

# SPONSORS

---

## sponsors

---

## sponsor_campaigns

---

## sponsor_rewards

---

## sponsor_impressions

---

# REFERRALS

---

## referrals

---

## referral_rewards

---

# PAYMENTS

---

## payment_providers

---

## payment_transactions

---

## payment_webhooks

---

# STORAGE

---

## uploads

---

## media_assets

---

# INFRASTRUCTURE

---

## jobs

---

## failed_jobs

---

## cache_locks

---

## audit_logs

One of the most important tables.

Stores

Actor

Action

Resource

Changes

Timestamp

IP

Device

---

# INDEXING STRATEGY

Always index

```
tenant_id

created_at

status

user_id

party_id

wallet_id
```

Composite indexes

Example

```
(user_id, created_at)

(tenant_id, status)

(party_id, created_at)
```

---

# PARTITIONING

Partition

analytics_events

wallet_transactions

audit_logs

notifications

by

Month

Future

Week

for high-volume deployments.

---

# FOREIGN KEY RULES

Financial Data

RESTRICT

Social Data

CASCADE

Analytics

SET NULL

Media

CASCADE

---

# SOFT DELETE POLICY

Soft delete

Users

Creators

Marketplace Items

Organizations

Messages

Card Packs

Never soft delete

Wallet Transactions

Purchases

Ledger

Audit Logs

Payment Transactions

Analytics Events

---

# JSON COLUMNS

Allowed

metadata

settings

preferences

payload

translations

Never store relational data inside JSON.

---

# ENUMS

Status

```
active

inactive

pending

archived

deleted
```

Party Status

```
draft

scheduled

live

completed

cancelled
```

Wallet Transaction Type

```
purchase

reward

refund

bonus

admin

withdrawal
```

---

# MIGRATION RULES

Every migration

Must be reversible.

Must include indexes.

Must include foreign keys.

Must update documentation.

---

# PERFORMANCE GUIDELINES

Maximum

100 Columns

per table.

Avoid nullable columns unless justified.

Use UUID consistently.

Never use SELECT \* in production code.

---

# BACKUP PRIORITY

Highest

Wallet

Purchases

Users

Marketplace

Organizations

Medium

Cards

Messages

Notifications

Low

Analytics

Temporary Data

---

# FUTURE TABLES

Reserved

```
ai_agents

plugins

developer_apps

api_keys

subscriptions

guilds

achievements

badges

quests

tournaments

season_passes

digital_collectibles
```

---

# Claude Code Instructions

When modifying the database:

1. Preserve referential integrity.
2. Never mutate immutable financial tables.
3. Index foreign keys.
4. Every migration must be reversible.
5. Document every schema change.
6. Never duplicate data unnecessarily.
7. Use UUIDs consistently.
8. Keep this document synchronized with migrations.

---

# Acceptance Criteria

The database schema is complete when:

- Every table is documented.
- Relationships are explicit.
- Indexes are defined.
- Multi-tenancy is enforced.
- Financial integrity is protected.
- Schema changes are versioned.
- Developers can implement features without guessing database structure.

---
