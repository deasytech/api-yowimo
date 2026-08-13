# Yowimo AI Host (Yowi) Architecture

**Version:** 1.0.0

**Status:** Living Engineering Specification

**Priority:** CRITICAL

**Owner:** AI Platform Team

**Depends On**

- 02_SYSTEM_ARCHITECTURE.md
- 05_API_STANDARDS.md
- 07_EVENT_CATALOG.md
- 08_GAME_ENGINE.md
- 09_REALTIME_ARCHITECTURE.md
- 10_QUEUE_AND_BACKGROUND_JOBS.md

---

# Purpose

The AI Host, known as **Yowi**, is the personality of Yowimo.

Yowi is far more than a chatbot.

Yowi is responsible for hosting parties, narrating games, engaging players, creating memorable moments, generating highlights, moderating interactions, and adapting gameplay in real time.

Every AI interaction must feel like a charismatic party host rather than an assistant.

---

# Core Responsibilities

Yowi is responsible for:

✓ Welcoming players

✓ Explaining game rules

✓ Reading cards aloud

✓ Narrating challenges

✓ Managing countdowns

✓ Responding to audience reactions

✓ Creating excitement

✓ Encouraging shy players

✓ Detecting low engagement

✓ Generating party recaps

✓ Creating highlight captions

✓ Suggesting next games

✓ Translating content

✓ Moderating conversations

---

# AI Design Principles

Yowi should always be:

Friendly

Funny

Energetic

Respectful

Inclusive

Confident

Natural

Fast

---

Yowi should NEVER be:

Judgmental

Aggressive

Embarrassing

Political

Offensive

Manipulative

Sexually explicit

Discriminatory

---

# AI Architecture

```text
Game Engine

↓

Domain Events

↓

AI Orchestrator

↓

Prompt Builder

↓

Provider Adapter

↓

LLM

↓

Voice

↓

Broadcast

↓

Players
```

---

# AI Modules

The AI Platform consists of

```
AI Orchestrator

↓

Context Manager

↓

Prompt Engine

↓

Memory Manager

↓

Voice Generator

↓

Moderation Engine

↓

Recommendation Engine

↓

Summary Engine

↓

Translation Engine
```

---

# AI Orchestrator

The Orchestrator coordinates all AI requests.

Responsibilities

Determine AI task

Build context

Choose provider

Dispatch request

Handle retries

Cache results

Emit events

---

# Provider Abstraction

The AI layer must never depend directly on one provider.

Supported Providers

OpenAI

Anthropic

Google Gemini

Future

Azure OpenAI

Local Models

---

Interface Example

```php
interface AIProvider
{
    generate();

    stream();

    moderate();

    embeddings();
}
```

Switching providers should require configuration only.

---

# AI Task Types

Narration

Voice

Translation

Moderation

Summarization

Recommendations

Challenge Creation

Highlight Captions

Marketing Copy

Sponsor Messages

---

# Personality System

Yowi supports personalities.

Current

Default

Future

Funny

Corporate

Romantic

Chaotic

Family Friendly

Educational

Celebrity Voices

---

# Personality Traits

Each personality defines

Vocabulary

Humor Level

Excitement

Energy

Emoji Usage

Speaking Speed

Encouragement Style

---

Example

Funny

```
High Energy

Frequent Jokes

Playful

Fast Pace
```

Corporate

```
Professional

Respectful

Minimal Slang

Encouraging
```

---

# Context Builder

Every prompt contains only relevant information.

Example Context

Party Type

Current Round

Current Turn

Player Name

Card Type

Difficulty

Language

Game Mode

Audience Size

Never include unrelated data.

---

# Prompt Structure

Every prompt follows

```
System Prompt

↓

Party Context

↓

Current Game State

↓

User Input

↓

Expected Output
```

---

# Prompt Versioning

Prompts are versioned.

```
Narrator_v1

Narrator_v2

Narrator_v3
```

Never overwrite production prompts.

---

# Memory Model

Yowi has **Session Memory** only.

Memory begins

Party Start

Memory ends

Party End

Yowi does NOT permanently remember users.

---

# Memory Includes

Player Names

Current Scores

Recent Challenges

Inside Jokes

Round History

Audience Mood

---

# Memory Excludes

Passwords

Personal Data

Private Messages

Financial Information

Authentication Data

---

# Voice Engine

Voice generation is independent.

Pipeline

```
Narration

↓

Text

↓

Voice Synthesis

↓

Audio File

↓

Broadcast
```

---

# Voice Personalities

Default

Funny

Calm

Corporate

Excited

Romantic

Future

Celebrity Packs

Premium Voices

---

# Voice Settings

Speed

Pitch

Emotion

Volume

Pause Length

Language

---

# AI Narration Events

Triggered by

Game Started

Round Started

Turn Started

Card Revealed

Challenge Completed

Vote Closed

Winner Announced

Game Finished

---

# AI Challenge Generation

Future Feature

Generate dynamic challenges based on

Players

Difficulty

Culture

Party Mood

Sponsor

Language

History

Never duplicate recent challenges.

---

# AI Recommendations

Recommend

Next Card

Next Game

Marketplace Packs

Friends

Sponsors

Party Themes

Future Events

Recommendations must be explainable.

---

# AI Translation

Supported

English

French

Spanish

Portuguese

Arabic

Swahili

Yoruba

Igbo

Hausa

Future

100+ Languages

---

# AI Moderation

Moderation checks

Chat

Voice Transcripts

Highlight Captions

User Content

AI Generated Text

---

Detect

Spam

Harassment

Threats

Bullying

Illegal Content

Excessive Profanity

Hate Speech

---

Moderation Actions

Warn

Mute

Block Message

Flag Moderator

Suspend User

---

# AI Summaries

At party completion

Generate

Party Summary

Funniest Moments

Top Quotes

MVP Commentary

Highlights

Share Caption

---

# Highlight Captions

Example

Instead of

```
Alex completed a dare.
```

Generate

```
Alex absolutely committed to the challenge and the room couldn't stop laughing.
```

Captions should feel human.

---

# AI Costs

Every AI request logs

Provider

Tokens

Latency

Cost

Purpose

Party

User

---

# Cost Optimization

Cache repeated prompts.

Reuse translations.

Batch analytics prompts.

Generate summaries asynchronously.

Avoid unnecessary LLM calls.

---

# Streaming

Narration supports streaming.

```
AI

↓

Chunks

↓

Voice

↓

Broadcast

↓

Players
```

Reduces perceived latency.

---

# Fallback Strategy

If Provider A fails

↓

Retry

↓

Switch Provider

↓

Use Cached Version

↓

Fallback Template

Gameplay must continue.

---

# AI Events

```
ai.started

ai.completed

ai.failed

ai.voice_generated

ai.summary_generated

ai.translation_completed

ai.challenge_generated

ai.recommendation_created
```

---

# Security

Never send

JWT

Passwords

Payment Data

Private Chats

Secrets

Personally sensitive information

to AI providers.

Only send the minimum context required.

---

# Monitoring

Track

Prompt Latency

Provider Errors

Cost Per Party

Average Tokens

Cache Hit Rate

Voice Generation Time

Moderation Accuracy

---

# Testing

Test

Prompt Quality

Voice Playback

Fallback Logic

Provider Switching

Moderation Rules

Latency Targets

---

# Future Features

AI Dungeon Master

AI Party Photographer

AI DJ

AI Story Mode

AI Trivia Creator

AI Theme Generator

AI Tournament Host

AI Coach

AI Avatar

---

# Claude Code Instructions

When implementing AI features:

1. Route all requests through the AI Orchestrator.
2. Never call providers directly from controllers or services.
3. Build prompts using the Context Builder.
4. Keep session memory temporary.
5. Log cost and latency.
6. Implement provider fallback.
7. Queue long-running AI tasks.
8. Update this document whenever a new AI capability or provider is introduced.

---

# Acceptance Criteria

The AI Host architecture is complete when:

- Yowi behaves consistently across all game modes.
- AI requests are provider-agnostic.
- Session memory is isolated to the current party.
- Voice, moderation, translation, and summaries are modular.
- Costs are measurable and optimizable.
- Gameplay continues even if an AI provider fails.

---
