# Yowimo Environment Variable Reference

**Version:** 1.0.0

**Status:** Infrastructure Configuration Specification

**Priority:** CRITICAL

**Owner:** Platform Engineering / DevOps

**Framework**

Laravel 12

React Native (Expo)

PostgreSQL

Redis

LiveKit

Laravel Reverb

Docker

GitHub Actions

AWS

**Depends On**

- 18_INFRASTRUCTURE_AND_DEVOPS.md
- 22_BACKEND_SERVICE_CATALOG.md
- 38_DATABASE_SCHEMA_REFERENCE.md

---

# Purpose

This document defines every environment variable used throughout Yowimo.

It provides

- Variable Name
- Purpose
- Default Value
- Required Status
- Environment Scope
- Security Classification
- Example Values

No environment variable should exist without documentation.

---

# Environment Philosophy

Configuration belongs in environment variables.

Never hardcode

Passwords

API Keys

Secrets

URLs

Database Credentials

Provider Tokens

---

# Environment Types

Development

```
.env
```

Testing

```
.env.testing
```

Staging

```
.env.staging
```

Production

```
.env.production
```

CI/CD

GitHub Secrets

Docker Secrets

AWS Secrets Manager

---

# Naming Convention

Uppercase

Snake Case

Examples

```
APP_NAME

APP_URL

REDIS_HOST

LIVEKIT_API_KEY
```

---

# Security Levels

Public

Safe to expose

Internal

Application only

Secret

Sensitive

Critical

Production credentials

---

# APPLICATION

---

## APP_NAME

Purpose

Application name.

Example

```
Yowimo
```

Required

Yes

Security

Public

---

## APP_ENV

Values

```
local

testing

staging

production
```

Required

Yes

---

## APP_KEY

Purpose

Laravel encryption key.

Required

Yes

Security

Critical

Rotate

Never automatically.

---

## APP_DEBUG

Values

```
true

false
```

Production

Always

```
false
```

---

## APP_URL

Example

```
https://api.yowimo.com
```

---

## APP_TIMEZONE

Example

```
UTC
```

---

## APP_LOCALE

Default

```
en
```

---

## APP_FALLBACK_LOCALE

```
en
```

---

# DATABASE

---

## DB_CONNECTION

```
pgsql
```

---

## DB_HOST

---

## DB_PORT

```
5432
```

---

## DB_DATABASE

---

## DB_USERNAME

---

## DB_PASSWORD

Security

Critical

---

## DB_SSLMODE

Production

```
require
```

---

# REDIS

---

## REDIS_CLIENT

```
phpredis
```

---

## REDIS_HOST

---

## REDIS_PORT

```
6379
```

---

## REDIS_PASSWORD

Secret

---

## REDIS_DB

Default

```
0
```

---

## REDIS_CACHE_DB

Default

```
1
```

---

## REDIS_QUEUE_DB

Default

```
2
```

---

# QUEUES

---

## QUEUE_CONNECTION

Production

```
redis
```

---

## QUEUE_FAILED_DRIVER

```
database-uuids
```

---

# CACHE

---

## CACHE_DRIVER

```
redis
```

---

## SESSION_DRIVER

```
redis
```

---

## BROADCAST_DRIVER

```
reverb
```

---

# REVERB

---

## REVERB_APP_ID

---

## REVERB_APP_KEY

Secret

---

## REVERB_APP_SECRET

Critical

---

## REVERB_HOST

---

## REVERB_PORT

Example

```
8080
```

---

## REVERB_SCHEME

```
https
```

---

# LIVEKIT

---

## LIVEKIT_URL

Example

```
wss://livekit.yowimo.com
```

---

## LIVEKIT_API_KEY

Secret

---

## LIVEKIT_API_SECRET

Critical

---

# STORAGE

---

## FILESYSTEM_DISK

Production

```
s3
```

---

## AWS_ACCESS_KEY_ID

Secret

---

## AWS_SECRET_ACCESS_KEY

Critical

---

## AWS_DEFAULT_REGION

Example

```
eu-west-1
```

---

## AWS_BUCKET

---

## AWS_URL

---

## AWS_ENDPOINT

Optional

---

# MAIL

---

## MAIL_MAILER

```
ses
```

Future

```
resend
```

---

## MAIL_HOST

---

## MAIL_PORT

---

## MAIL_USERNAME

---

## MAIL_PASSWORD

Secret

---

## MAIL_FROM_ADDRESS

---

## MAIL_FROM_NAME

---

# PUSH NOTIFICATIONS

---

## FCM_PROJECT_ID

---

## FCM_PRIVATE_KEY

Critical

---

## FCM_CLIENT_EMAIL

---

# CLERK

---

## CLERK_SECRET_KEY

Critical

---

## CLERK_PUBLISHABLE_KEY

Public

---

## CLERK_WEBHOOK_SECRET

Critical

---

## CLERK_JWT_ISSUER

---

# AI

---

## OPENAI_API_KEY

Critical

---

## OPENAI_MODEL

Example

```
gpt-5.5
```

---

## ANTHROPIC_API_KEY

---

## ANTHROPIC_MODEL

Example

```
claude-sonnet-5
```

---

## GEMINI_API_KEY

---

## GEMINI_MODEL

Example

```
gemini-2.5-pro
```

---

## DEEPSEEK_API_KEY

---

## AI_DEFAULT_PROVIDER

Example

```
openai
```

---

## AI_FALLBACK_PROVIDER

Example

```
anthropic
```

---

## AI_MAX_COST_PER_REQUEST

Example

```
0.05
```

---

# PAYMENTS

---

## PAYSTACK_PUBLIC_KEY

Public

---

## PAYSTACK_SECRET_KEY

Critical

---

## PAYSTACK_WEBHOOK_SECRET

Critical

---

## STRIPE_SECRET_KEY

Future

---

## STRIPE_WEBHOOK_SECRET

Future

---

# GOOGLE

---

## GOOGLE_MAPS_API_KEY

Secret

---

## GOOGLE_CLIENT_ID

---

## GOOGLE_CLIENT_SECRET

Critical

---

# SOCIAL LOGIN

---

## APPLE_CLIENT_ID

---

## APPLE_PRIVATE_KEY

Critical

---

## FACEBOOK_CLIENT_ID

---

## FACEBOOK_CLIENT_SECRET

Critical

---

# CDN

---

## CDN_URL

Example

```
https://cdn.yowimo.com
```

---

# SEARCH

---

## SCOUT_DRIVER

Future

```
meilisearch
```

---

## MEILISEARCH_HOST

---

## MEILISEARCH_KEY

Secret

---

# OBSERVABILITY

---

## LOG_CHANNEL

```
stack
```

---

## LOG_LEVEL

Production

```
warning
```

---

## SENTRY_DSN

---

## SENTRY_ENVIRONMENT

---

# HORIZON

---

## HORIZON_PREFIX

Example

```
yowimo
```

---

# FEATURE FLAGS

---

## FEATURE_AI_HOST

```
true
```

---

## FEATURE_MARKETPLACE

```
true
```

---

## FEATURE_ENTERPRISE

```
true
```

---

## FEATURE_SPONSORS

```
true
```

---

## FEATURE_EXPERIMENTAL_AI

```
false
```

---

# RATE LIMITS

---

## RATE_LIMIT_API

Example

```
120
```

Requests per minute.

---

## RATE_LIMIT_AI

Example

```
30
```

---

## RATE_LIMIT_UPLOADS

Example

```
20
```

---

# SECURITY

---

## HASH_DRIVER

```
bcrypt
```

Future

```
argon2id
```

---

## PASSWORD_TIMEOUT

Example

```
10800
```

---

## SESSION_LIFETIME

Example

```
120
```

Minutes

---

# ANALYTICS

---

## ANALYTICS_ENABLED

```
true
```

---

## ANALYTICS_RETENTION_DAYS

Example

```
730
```

---

# FILES

---

## MAX_UPLOAD_SIZE_MB

Example

```
100
```

---

## IMAGE_MAX_WIDTH

Example

```
4096
```

---

## VIDEO_MAX_DURATION

Example

```
600
```

Seconds

---

# BACKUPS

---

## BACKUP_ENABLED

```
true
```

---

## BACKUP_DISK

```
s3
```

---

## BACKUP_RETENTION_DAYS

Example

```
30
```

---

# DOCKER

---

## DOCKER_IMAGE_TAG

---

## DOCKER_REGISTRY

---

# GITHUB ACTIONS

---

## DEPLOY_ENVIRONMENT

Example

```
production
```

---

## DEPLOY_BRANCH

Example

```
main
```

---

# MONITORING

---

## GRAFANA_URL

---

## PROMETHEUS_ENDPOINT

---

## HEALTHCHECK_URL

---

# FUTURE VARIABLES

```
KUBERNETES_CLUSTER

EDGE_REGION

AI_VECTOR_DATABASE

OPEN_SEARCH_ENDPOINT

PLUGIN_SIGNING_KEY

AR_ENGINE_KEY

VR_ENGINE_KEY

PUBLIC_API_KEY

DEVELOPER_PORTAL_URL
```

---

# Secret Rotation Policy

Rotate Every

API Keys

90 Days

Webhook Secrets

180 Days

Database Passwords

180 Days

JWT Secrets

Immediately upon compromise

---

# Validation Rules

Every required variable

Must be validated during application boot.

Missing critical variables

↓

Application refuses to start.

---

# CI/CD

Secrets stored in

GitHub Actions Secrets

AWS Secrets Manager

Docker Secrets

Never committed to Git.

---

# Security Rules

Never

Commit `.env`

Log secrets

Expose credentials

Share production values

Reuse secrets across environments

---

# Local Development

Use

```
.env.example
```

Every new variable must be added to

- `.env.example`
- This document
- Deployment documentation

---

# Claude Code Instructions

When introducing configuration:

1. Never hardcode configuration values.
2. Use descriptive environment variable names.
3. Add new variables to `.env.example`.
4. Document every variable here.
5. Classify each variable by security level.
6. Validate required variables at startup.
7. Store production secrets in a secret manager.
8. Update this document whenever configuration changes.

---

# Acceptance Criteria

The Environment Variable Reference is complete when:

- Every configuration variable is documented.
- Secrets are classified.
- Environment-specific behavior is defined.
- Startup validation is enforced.
- Secret rotation policies are documented.
- Developers can configure any environment without guesswork.

---
