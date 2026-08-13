# Yowimo Configuration Reference

**Version:** 1.0.0

**Status:** Platform Configuration Specification

**Priority:** CRITICAL

**Owner:** Platform Engineering

**Applies To**

Backend

Mobile

Admin

Infrastructure

AI

Marketplace

Enterprise

Creator Platform

**Depends On**

- 18_INFRASTRUCTURE_AND_DEVOPS.md
- 22_BACKEND_SERVICE_CATALOG.md
- 49_ENVIRONMENT_VARIABLE_REFERENCE.md

---

# Purpose

This document defines every configurable behavior in Yowimo.

Unlike environment variables, configuration values define **how the platform behaves**, not **where it runs**.

Configuration should be centralized.

Never hardcode business rules.

---

# Configuration Hierarchy

```text
Application

↓

Domain

↓

Feature

↓

Organization

↓

User
```

Higher levels override lower levels only where explicitly permitted.

---

# Configuration Sources

Priority Order

```text
Runtime Overrides

↓

Database Configuration

↓

Config Files

↓

Environment Variables

↓

Framework Defaults
```

---

# Configuration Categories

- Application
- Authentication
- Users
- Friends
- Parties
- Gameplay
- Wallet
- Marketplace
- Creator
- Enterprise
- Notifications
- AI
- Analytics
- Security
- Storage
- Payments
- Moderation
- Localization
- Feature Flags

---

# APPLICATION

---

## General

```yaml
application:
    name: Yowimo
    timezone: UTC
    locale: en
    fallback_locale: en
    maintenance_mode: false
```

---

## Versioning

```yaml
version:
    api: v1
    mobile_minimum: 1.0.0
    admin_minimum: 1.0.0
```

---

# AUTHENTICATION

```yaml
authentication:
    provider: Clerk
    require_email_verification: true
    require_phone_verification: false
    allow_social_login: true
    session_timeout: 120
```

---

# USER SETTINGS

```yaml
users:
    minimum_age: 18
    username_min_length: 3
    username_max_length: 30
    bio_max_length: 500
    avatar_required: false
```

---

# FRIEND SYSTEM

```yaml
friends:
    max_friends: 5000
    max_requests_per_day: 100
    allow_blocking: true
    allow_search: true
```

---

# PARTY SETTINGS

```yaml
party:
    minimum_players: 2
    maximum_players: 100
    invitation_expiry_hours: 48
    auto_close_after_hours: 12
    allow_rejoin: true
```

---

## Party Types

```yaml
party_types:
    public: true
    private: true
    organization: true
    hybrid: true
```

---

# GAMEPLAY

```yaml
gameplay:
    default_rounds: 10
    default_turn_seconds: 45
    countdown_seconds: 10
    allow_skip: true
    allow_votes: true
```

---

## Scoring

```yaml
scoring:
    challenge_completion: 100
    bonus_streak: 25
    participation: 10
```

---

# WALLET

```yaml
wallet:
    enabled: true
    starting_balance: 0
    allow_transfers: false
    allow_refunds: true
```

---

## Rewards

```yaml
rewards:
    welcome_bonus: 100
    referral_bonus: 250
    daily_login: 25
    challenge_completion: 20
```

---

## Token Economy

```yaml
tokens:
    minimum_purchase: 100
    maximum_purchase: 100000
    expiration_days: null
```

---

# MARKETPLACE

```yaml
marketplace:
    enabled: true
    creator_revenue_share: 70
    platform_revenue_share: 30
    refunds_allowed: true
```

---

## Products

```yaml
products:
    minimum_price: 100
    maximum_price: 50000
    review_required: true
```

---

# CREATOR PLATFORM

```yaml
creator:
    verification_required: true
    minimum_age: 18
    payouts_enabled: true
    featured_slots: 20
```

---

## Creator Payouts

```yaml
payouts:
    minimum_amount: 5000
    frequency: monthly
    currency: NGN
```

---

# ENTERPRISE

```yaml
enterprise:
    enabled: true
    max_employees: 100000
    custom_branding: true
    analytics_enabled: true
```

---

## Organization

```yaml
organization:
    invite_expiration_days: 14
    max_departments: 500
```

---

# CHAT

```yaml
chat:
    enabled: true
    max_message_length: 2000
    allow_images: true
    allow_audio: true
    allow_video: false
```

---

# VOICE

```yaml
voice:
    enabled: true
    max_participants: 100
    recording_enabled: false
```

---

# VIDEO

```yaml
video:
    enabled: false
    max_participants: 20
```

Future Feature

---

# AI

```yaml
ai:
    enabled: true
    provider: openai
    moderation_enabled: true
    recommendations_enabled: true
```

---

## AI Host

```yaml
host:
    personality: energetic
    language: auto
    maximum_response_words: 80
```

---

## AI Safety

```yaml
moderation:
    enabled: true
    strict_mode: true
    profanity_filter: true
```

---

# NOTIFICATIONS

```yaml
notifications:
    push: true
    email: true
    sms: false
    realtime: true
```

---

## Quiet Hours

```yaml
quiet_hours:
    enabled: true
    start: "22:00"
    end: "07:00"
```

---

# ANALYTICS

```yaml
analytics:
    enabled: true
    realtime: true
    retention_days: 730
```

---

## Metrics

```yaml
metrics:
    track_sessions: true
    track_retention: true
    track_marketplace: true
```

---

# SECURITY

```yaml
security:
    mfa_required_for_admins: true
    password_reset_expiry: 30
    max_login_attempts: 5
```

---

## Session

```yaml
session:
    single_device: false
    invalidate_on_password_change: true
```

---

# STORAGE

```yaml
storage:
    images_disk: s3
    videos_disk: s3
    documents_disk: s3
```

---

## Upload Limits

```yaml
uploads:
    image_mb: 10
    video_mb: 100
    audio_mb: 25
```

---

# PAYMENTS

```yaml
payments:
    paystack: true
    stripe: false
    refunds: true
```

---

## Currency

```yaml
currency:
    default: NGN
    supported:
        - NGN
        - USD
        - EUR
        - GBP
```

---

# MODERATION

```yaml
moderation:
    auto_review: true
    creator_review: true
    chat_filter: true
```

---

# LOCALIZATION

```yaml
languages:
    default: en
    supported:
        - en
        - fr
        - es
        - pt
```

---

## Regions

```yaml
regions:
    africa: true
    europe: true
    americas: true
    asia: true
```

---

# FEATURE FLAGS

```yaml
features:
    ai_host: true
    marketplace: true
    enterprise: true
    referrals: true
    sponsors: true
```

---

## Experimental

```yaml
experimental:
    tournaments: false
    guilds: false
    achievements: false
    ar_mode: false
    vr_mode: false
```

---

# ADMIN CONFIGURATION

```yaml
admin:
    maintenance_access: admins_only
    audit_logging: true
    export_enabled: true
```

---

# CACHE

```yaml
cache:
    user_profile: 300
    marketplace: 600
    leaderboard: 60
    organizations: 300
```

Values are in seconds.

---

# RATE LIMITS

```yaml
limits:
    api_requests_per_minute: 120
    ai_requests_per_minute: 30
    uploads_per_hour: 50
```

---

# SCHEDULER

```yaml
scheduler:
    leaderboard: "*/5 * * * *"
    wallet_reconciliation: "0 2 * * *"
    analytics: "0 1 * * *"
```

---

# LOGGING

```yaml
logging:
    audit: true
    security: true
    performance: true
```

---

# BACKUPS

```yaml
backup:
    enabled: true
    frequency: daily
    retention_days: 30
```

---

# CONFIGURATION VALIDATION

Every configuration entry must define

- Type
- Default
- Validation Rules
- Allowed Values
- Documentation

---

# Runtime Configuration

Configuration may be overridden by

Organization Settings

↓

Workspace Settings

↓

User Preferences

Where explicitly supported.

---

# Configuration Versioning

Every configuration change records

- Previous Value
- New Value
- Changed By
- Timestamp
- Reason

---

# Audit Rules

The following changes must always be audited

- Security
- Payments
- Wallet
- Marketplace Revenue
- AI Configuration
- Feature Flags
- Organization Settings

---

# Future Configuration Groups

```yaml
plugins:
developer_api:
guilds:
tournaments:
season_pass:
quests:
badges:
achievements:
digital_collectibles:
ar:
vr:
edge_ai:
```

---

# Claude Code Instructions

When introducing configuration:

1. Never hardcode business rules.
2. Add new options to the appropriate configuration group.
3. Validate configuration values.
4. Support organization-level overrides where applicable.
5. Keep configuration backward compatible.
6. Audit sensitive configuration changes.
7. Document every configuration option.
8. Update this document whenever configuration changes.

---

# Acceptance Criteria

The Configuration Reference is complete when:

- Every configurable behavior is documented.
- Business rules are configurable.
- Organization overrides are supported where appropriate.
- Sensitive changes are audited.
- Developers can understand platform behavior without reading code.
- Configuration remains centralized and versioned.

---
