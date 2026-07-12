# Yowimo AI Prompt Library

**Version:** 1.0.0

**Status:** Core AI Engineering Specification

**Priority:** CRITICAL

**Owner:** AI Platform Team

**Depends On**

- 11_AI_HOST_ARCHITECTURE.md
- 17_MODERATION_AND_SAFETY.md
- 22_BACKEND_SERVICE_CATALOG.md
- 25_API_SDK_AND_CLIENT_LIBRARY.md

---

# Purpose

The AI Prompt Library is the single source of truth for every Large Language Model (LLM) prompt used throughout Yowimo.

Prompts are treated as production assets.

They are:

- Versioned
- Tested
- Reviewed
- Measured
- Documented

Prompts are never hardcoded inside controllers or services.

---

# Philosophy

A prompt is code.

It should have:

✓ Version History

✓ Tests

✓ Owners

✓ Documentation

✓ Metrics

✓ Rollback

---

# AI Architecture

```text
Application

↓

AI Orchestrator

↓

Prompt Library

↓

Context Builder

↓

Provider

↓

Safety Layer

↓

Validation

↓

Response
```

---

# Supported AI Providers

Primary

OpenAI

Secondary

Anthropic

Fallback

Google Gemini

Future

DeepSeek

Mistral

Grok

Self-hosted Models

---

# Prompt Categories

Game Host

Storytelling

Challenges

Moderation

Recommendations

Marketplace

Corporate

Translations

Highlights

Notifications

Support

Analytics

Administration

---

# Prompt Registry

Every prompt has

ID

Name

Version

Owner

Description

Input Schema

Output Schema

Safety Rules

Provider Compatibility

---

# Prompt Metadata

Example

```
ID

AI_HOST_001

Version

1.2.0

Owner

Platform AI

Status

Production
```

---

# Prompt Versioning

Every prompt follows

```
Major.Minor.Patch
```

Major

Breaking behavior

Minor

Improved wording

Patch

Grammar

Small optimizations

---

# AI Host Prompt

Purpose

Run live parties.

Responsibilities

Welcome players

Explain rules

Announce turns

Celebrate winners

Maintain energy

Handle pauses

Never reveal hidden cards.

---

# Storytelling Prompt

Purpose

Create immersive party narration.

Style

Energetic

Funny

Friendly

Short

Conversational

---

# Challenge Prompt

Generate

Truth

Dare

Icebreaker

Trivia

Corporate Challenges

Couples Challenges

Holiday Challenges

---

Prompt Inputs

Game Type

Difficulty

Age Rating

Player Count

Country

Language

Party Mood

---

# Recommendation Prompt

Suggest

Card Packs

Friends

Games

Themes

Marketplace Items

Sponsors

Based on

History

Preferences

Time

Season

Activity

---

# Highlight Prompt

Generate

Party Recap

Funny Moments

Captions

Titles

Summary

Social Sharing Text

---

Output Example

```
"Alex survived the impossible mustard challenge and somehow convinced everyone to join in. Chaos level: 11/10."
```

---

# Translation Prompt

Supports

Real-time Translation

Card Translation

Chat Translation

Party Summaries

Notifications

Marketplace

---

Supported Languages

English

French

Spanish

Portuguese

Arabic

Yoruba

Igbo

Hausa

Future

100+

---

# Moderation Prompt

Detect

Harassment

Spam

Bullying

Threats

Scams

Toxicity

Self Harm

Violence

Hate Speech

Explicit Content

---

Moderation returns

Safe

Warning

Block

Escalate

---

# Support Prompt

Assist users with

Wallet

Marketplace

Parties

Friends

AI Host

Settings

Account

Never modify accounts directly.

---

# Corporate Prompt

Generate

Icebreakers

Team Building

Workshop Activities

Meeting Games

Training Activities

Professional Tone

---

# Marketplace Prompt

Generate

Product Descriptions

Bundle Names

Promotional Copy

Seasonal Content

SEO Metadata

---

# Notification Prompt

Generate

Titles

Bodies

Localized Variants

Marketing Copy

Personalized Messages

---

# Analytics Prompt

Generate

Executive Summaries

Growth Insights

Player Trends

Weekly Reports

Sponsor Reports

---

# AI Personalities

Current

Yowi

Future

Comedian

Game Show Host

Storyteller

Corporate Facilitator

Romantic Host

Sports Commentator

Mystery Narrator

Custom Voices

---

# Personality Traits

Yowi

Friendly

Playful

Confident

Inclusive

Funny

Supportive

Never sarcastic or insulting.

---

# Prompt Inputs

Always structured.

Example

```json
{
    "party_type": "Truth or Dare",
    "players": 8,
    "language": "en",
    "difficulty": "medium"
}
```

---

# Prompt Outputs

Always structured.

Never return unstructured text when JSON is expected.

---

# Context Builder

Context may include

Party

Players

Scores

Language

Season

History

Purchased Packs

Preferences

Never include sensitive personal information.

---

# Prompt Testing

Every prompt must be tested for

Accuracy

Tone

Safety

Latency

Cost

Consistency

Localization

---

# Prompt Evaluation

Track

Success Rate

Average Tokens

Latency

User Satisfaction

Fallback Rate

Moderation Rate

---

# Prompt Caching

Cache

Translations

Recommendations

Summaries

Marketplace Copy

Avoid repeated expensive requests.

---

# Cost Optimization

Use

Smaller Models

↓

Medium Models

↓

Large Models

based on task complexity.

---

# Safety Guardrails

Every response passes through

Policy Filters

↓

Moderation

↓

Validation

↓

Formatting

↓

Delivery

---

# Hallucination Prevention

AI must never invent

Wallet Balances

Purchases

Friends

Scores

Transactions

Real Events

Always use provided context.

---

# Prompt Storage

Prompts stored as

Versioned Files

or

Database Templates

Never inline in source code.

---

# Prompt Approval Workflow

Draft

↓

Review

↓

Testing

↓

Approval

↓

Production

↓

Monitoring

---

# Fallback Strategy

If AI fails

↓

Retry

↓

Switch Provider

↓

Use Cached Response

↓

Use Static Template

---

# Metrics

Track

Prompt Usage

Cost

Tokens

Latency

Failures

Provider Performance

User Feedback

---

# Security

Never include

Passwords

API Keys

Payment Data

Private Messages

Secrets

Internal IDs

inside prompts.

---

# Documentation

Each prompt includes

Purpose

Inputs

Outputs

Examples

Version History

Known Limitations

---

# Future AI Features

Persistent Memory

Emotion Detection

Voice Cloning

Personalized Hosts

AI Party Planning

AI Tournament Commentary

AI Dungeon Master

Vision Models

Video Understanding

---

# Claude Code Instructions

When implementing AI:

1. Never hardcode prompts.
2. Use prompt IDs.
3. Version every prompt.
4. Validate all AI output.
5. Apply moderation before delivery.
6. Cache reusable responses.
7. Monitor cost and latency.
8. Update this document whenever prompts are added or modified.

---

# Acceptance Criteria

The AI Prompt Library is complete when:

- Every AI capability references a versioned prompt.
- Prompts are documented and testable.
- AI output is validated and moderated.
- Multiple providers are supported.
- Prompt costs and performance are measurable.
- New prompts can be added without changing application code.

---
