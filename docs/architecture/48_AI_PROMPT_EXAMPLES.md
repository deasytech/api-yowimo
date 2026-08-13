# Yowimo AI Prompt Library

**Version:** 1.0.0

**Status:** AI Prompt Engineering Specification

**Priority:** CRITICAL

**Owner:** AI Engineering Team

**Architecture**

AI Orchestrator

Prompt Registry

Prompt Versioning

Provider Abstraction

Moderation Pipeline

Cost Optimization

**Supported Providers**

OpenAI

Anthropic

Google Gemini

DeepSeek

Future Local Models

---

# Purpose

This document is the central repository for every AI prompt used within Yowimo.

Every production prompt must be

- Versioned
- Tested
- Documented
- Measured
- Reviewable

No prompt should exist inside application code.

All prompts are loaded from the Prompt Registry.

---

# Prompt Philosophy

Prompts are software.

They should be

Version Controlled

Reusable

Observable

Testable

Auditable

Upgradeable

---

# Prompt Lifecycle

```text
Design

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

↓

Iteration
```

---

# Prompt Template

Every prompt must define

```
Prompt ID

Name

Version

Purpose

Owner

Model

Temperature

Max Tokens

Input Variables

Output Schema

Fallback Prompt

Cost Estimate

Latency Target
```

---

# Standard Prompt Format

```text
SYSTEM

Context

Rules

Behavior

USER

Variables

Expected Task

ASSISTANT

Structured Output
```

---

# Global AI Rules

All AI models must

✓ Be respectful

✓ Avoid offensive language

✓ Protect user privacy

✓ Never fabricate financial information

✓ Avoid harmful advice

✓ Support localization

✓ Produce structured responses

---

# Prompt Categories

Party Host

Gameplay

Creator

Marketplace

Corporate

Moderation

Translation

Recommendation

Analytics

Customer Support

Marketing

Summaries

Voice

Notifications

---

# PARTY HOST PROMPTS

---

## AI_HOST_WELCOME

Prompt ID

```
AI_HOST_001
```

Purpose

Welcome players into a party.

System Prompt

```text
You are Yowimo's energetic AI Party Host.

Your job is to create excitement, encourage participation,
and keep the energy high.

Never insult players.

Never reveal private information.

Keep responses under 80 words.

Maintain an upbeat and friendly tone.
```

Input

```json
{
    "party_name": "",
    "host_name": "",
    "player_count": 8,
    "game": "Truth or Dare"
}
```

Expected Output

```json
{
    "message": "..."
}
```

Target

<2 seconds

---

## AI_HOST_NEXT_ROUND

Purpose

Introduce the next round.

Example Output

> Round two begins! Things are about to get even more interesting. Let's see who takes the lead!

---

## AI_HOST_WINNER

Purpose

Celebrate the winner.

Rules

Never humiliate losing players.

Encourage another round.

---

# GAMEPLAY PROMPTS

---

## GAME_HINT_GENERATOR

Purpose

Generate hints for trivia games.

Variables

Difficulty

Language

Topic

Age Rating

---

## GAME_STORYTELLER

Purpose

Generate immersive story narration.

Supports

Fantasy

Mystery

Adventure

Corporate Training

Education

---

## DARE_ENHANCER

Purpose

Rewrite dares to improve clarity while respecting the selected age rating and regional guidelines.

---

# MODERATION PROMPTS

---

## CONTENT_MODERATION

Prompt ID

```
MOD_001
```

Purpose

Review user-generated content.

Instructions

Detect

Hate Speech

Harassment

Sexual Content

Violence

Spam

Scams

Personal Information

Self Harm

Illegal Content

Output

```json
{
    "safe": true,
    "category": "",
    "confidence": 0.98,
    "reason": ""
}
```

---

## CHAT_MODERATION

Purpose

Moderate party chat messages.

Must respond in

<500ms

---

## CREATOR_CONTENT_REVIEW

Purpose

Review submitted card packs.

Checks

Appropriate language

Copyright concerns

Spam

Unsafe content

---

# TRANSLATION PROMPTS

---

## UNIVERSAL_TRANSLATION

Purpose

Translate gameplay content.

Requirements

Maintain tone

Preserve emojis

Do not translate usernames

Support

100+ languages

---

## PARTY_CHAT_TRANSLATION

Purpose

Realtime translation.

Maximum latency

<300ms

---

# RECOMMENDATION PROMPTS

---

## GAME_RECOMMENDER

Purpose

Recommend games.

Input

Age

Mood

Player Count

Previous History

Language

Output

Top 5 games

---

## MARKETPLACE_RECOMMENDER

Purpose

Recommend creator packs.

Consider

Purchases

Favorites

Friends

Trending

Language

---

## FRIEND_RECOMMENDER

Purpose

Suggest friends.

Signals

Contacts

Mutual Friends

Organizations

Gameplay History

---

# CREATOR PROMPTS

---

## PACK_DESCRIPTION_GENERATOR

Purpose

Generate marketplace descriptions.

Style

Fun

Concise

Search Optimized

---

## TAG_GENERATOR

Generate

Tags

Keywords

Categories

Difficulty

---

## TITLE_GENERATOR

Generate

Creative titles

Maximum

60 characters

---

# CORPORATE PROMPTS

---

## ICEBREAKER_GENERATOR

Purpose

Generate corporate-safe icebreakers.

Rules

No alcohol

No romance

No politics

No religion

Professional tone

---

## TRAINING_SUMMARY

Summarize

Corporate event

Attendance

Engagement

Highlights

---

## TEAM_INSIGHTS

Generate

Participation insights

Without exposing private employee information.

---

# ANALYTICS PROMPTS

---

## PARTY_SUMMARY

Purpose

Summarize completed parties.

Output

Most Active Player

Funniest Moment

Top Score

Favorite Game

Participation Rate

---

## WEEKLY_INSIGHTS

Summarize

Weekly activity.

---

## EXECUTIVE_SUMMARY

Enterprise dashboard.

Audience

Executives

Tone

Professional

---

# NOTIFICATION PROMPTS

---

## REWARD_NOTIFICATION

Example

> 🎉 Congratulations! You've earned 100 tokens for completing tonight's challenge.

---

## FRIEND_NOTIFICATION

Example

> Alex accepted your friend request.

---

## MARKETPLACE_NOTIFICATION

Example

> Your latest card pack has reached 1,000 downloads!

---

# CUSTOMER SUPPORT

---

## SUPPORT_REPLY

Purpose

Draft support replies.

Rules

Professional

Helpful

Concise

Never promise unavailable features.

---

## REFUND_EXPLANATION

Purpose

Explain refund decisions.

Tone

Empathetic

Clear

---

# MARKETING

---

## PUSH_COPY

Maximum

120 characters

Examples

Party reminders

Marketplace updates

Events

---

## EMAIL_SUBJECT

Maximum

60 characters

---

## SOCIAL_MEDIA_CAPTION

Generate

Instagram

X

LinkedIn

Facebook

TikTok

versions

---

# VOICE PROMPTS

---

## AI_VOICE_HOST

Purpose

Generate spoken narration.

Rules

Natural pacing

Short sentences

Clear pronunciation

No abbreviations

---

## AI_EMOTION

Generate

Happy

Excited

Calm

Serious

Suspenseful

tones

---

# IMAGE PROMPTS

---

## HIGHLIGHT_IMAGE

Purpose

Generate artwork prompts.

Input

Theme

Players

Game

Mood

Output

Optimized image prompt.

---

# PROMPT VERSIONING

Example

```
AI_HOST_001

v1.0

↓

v1.1

↓

v2.0
```

Old versions remain archived.

---

# OUTPUT VALIDATION

Every structured prompt returns

```json
{
    "success": true,
    "data": {}
}
```

Invalid responses

↓

Retry

↓

Fallback Model

↓

Fallback Prompt

---

# TEMPERATURE GUIDELINES

Creative

0.9

Storytelling

0.8

Party Host

0.7

Recommendations

0.5

Translation

0.2

Moderation

0.1

---

# TOKEN LIMITS

Party Host

400

Moderation

250

Translation

300

Summaries

800

Storytelling

1200

---

# COST TARGETS

Average AI Cost

< $0.01

per party interaction.

Enterprise

Configurable.

---

# LATENCY TARGETS

Moderation

<500ms

Translation

<300ms

Host

<2s

Recommendations

<1s

Summaries

<5s

---

# FALLBACK STRATEGY

Primary Model

↓

Secondary Model

↓

Cached Response

↓

Static Template

---

# Prompt Testing

Every prompt tested for

Accuracy

Latency

Cost

Safety

Localization

Regression

---

# Monitoring

Track

Latency

Cost

Success Rate

Fallback Usage

Hallucination Reports

Safety Flags

---

# Security

Never send

Passwords

Payment Information

JWT Tokens

Private Messages (unless explicitly required and authorized)

Personally Identifiable Information

to AI providers.

---

# Future Prompts

```
AI Dungeon Master

AI Tournament Host

AI Relationship Coach

AI Fitness Challenges

AI Music DJ

AI Trivia Writer

AI Event Planner

AI Corporate Coach

AI Character Voices

AI Avatar Generator
```

---

# Claude Code Instructions

When implementing AI prompts:

1. Never hardcode prompts.
2. Store prompts in the Prompt Registry.
3. Version every prompt.
4. Define structured outputs whenever possible.
5. Measure latency and cost.
6. Use fallback models.
7. Moderate AI responses before returning them.
8. Update this document whenever a new production prompt is added.

---

# Acceptance Criteria

The AI Prompt Library is complete when:

- Every production prompt is documented.
- Prompts are versioned.
- Inputs and outputs are standardized.
- Safety rules are enforced.
- Costs and latency are monitored.
- Prompt evolution is traceable.
- Developers can introduce new AI features without embedding prompts directly in code.

---
