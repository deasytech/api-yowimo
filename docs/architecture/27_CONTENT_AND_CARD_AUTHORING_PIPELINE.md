# Yowimo Content & Card Authoring Pipeline

**Version:** 1.0.0

**Status:** Core Content Specification

**Priority:** CRITICAL

**Owner:** Content Team

**Contributors**

- Game Designers
- AI Team
- Content Editors
- Moderators
- Localization Team

**Depends On**

- 08_GAME_ENGINE.md
- 13_MARKETPLACE_ARCHITECTURE.md
- 17_MODERATION_AND_SAFETY.md
- 26_AI_PROMPT_LIBRARY.md

---

# Purpose

Content is the heart of Yowimo.

Without high-quality cards, the platform has no long-term engagement.

This document defines how every piece of playable content is created, reviewed, localized, published, analyzed, and retired.

---

# Philosophy

Every card should be

✓ Fun

✓ Safe

✓ Replayable

✓ Inclusive

✓ Localizable

✓ Tagged

✓ Versioned

✓ Measurable

---

# Content Lifecycle

```text
Idea

↓

Draft

↓

Review

↓

Testing

↓

Localization

↓

Approval

↓

Publishing

↓

Analytics

↓

Revision

↓

Archive
```

---

# Content Types

Truth

Dare

Never Have I Ever

Would You Rather

Icebreaker

Trivia

Charades

Quiz

Corporate

Team Building

Dating

Couples

Family

Holiday

Sponsor

AI Generated

Future Mini Games

---

# Card Entity

Fields

```
id

title

prompt

category

difficulty

age_rating

language

status

version

tags

author

metadata

created_at
```

---

# Card Status

Draft

Under Review

Needs Revision

Approved

Published

Archived

Deprecated

---

# Card Categories

Truth

Dare

Never Have I Ever

Icebreaker

Trivia

Corporate

Romantic

Funny

Educational

Holiday

Sponsored

---

# Difficulty Levels

Very Easy

Easy

Medium

Hard

Extreme

AI uses difficulty when generating recommendations.

---

# Age Ratings

Everyone

13+

16+

18+

Regional restrictions supported.

---

# Card Metadata

Store

Estimated Duration

Energy Level

Embarrassment Level

Physical Activity

Voice Required

Props Required

Group Size

Alcohol Related

AI Friendly

---

# Content Tags

Examples

Funny

Romantic

Spicy

Physical

Creative

Music

Dance

Office

Holiday

Birthday

Weekend

Family

Friends

Quick

Long

---

Cards may have multiple tags.

---

# Content Packs

Cards belong to Packs.

Examples

Classic Truth

Ultimate Dare

Friday Night

Office Icebreakers

Christmas Pack

Valentine Pack

Corporate Team Builder

Creator Collection

---

# Pack Entity

Fields

```
id

name

description

cover

price

visibility

status

language

version
```

---

# Content Ownership

Packs may be

Free

Premium

Sponsored

Corporate

Creator

Seasonal

---

# Card Authoring

Authors create cards using

Admin Panel

or

AI Assisted Authoring

---

Required Fields

Prompt

Category

Difficulty

Age Rating

Tags

Language

---

# AI Assisted Authoring

AI may suggest

New Cards

Variants

Translations

Difficulty Adjustments

Tag Suggestions

AI suggestions always require human approval.

---

# Content Review

Editors verify

Grammar

Fun

Originality

Clarity

Safety

Localization

Replayability

---

# Content Moderation

Reject

Illegal Content

Hate Speech

Explicit Sexual Content

Dangerous Challenges

Self Harm

Violence

Discrimination

---

# Regional Rules

Different countries may hide

Alcohol Cards

Dating Cards

Political Cards

Sensitive Topics

---

# Localization Workflow

Original

↓

Translation

↓

Review

↓

Native Speaker Validation

↓

Publish

---

Languages

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

# Content Versioning

Every update creates

New Version

Previous versions remain archived.

Never overwrite published cards.

---

# Content Scheduling

Support

Immediate Publish

Future Publish

Seasonal Publish

Timed Expiration

---

# Seasonal Content

Examples

Christmas

Halloween

Valentine

Ramadan

Easter

New Year

World Cup

Back To School

Automatically activates by schedule.

---

# Sponsored Content

Sponsors may publish

Branded Challenges

Sponsored Trivia

Giveaways

Campaign Cards

Must clearly display

Sponsored Badge

---

# Corporate Content

Professional

Inclusive

Safe

Short

Goal-Oriented

No romantic or explicit themes.

---

# Creator Content (Future)

Creators may submit

Card Packs

Trivia Packs

Holiday Packs

Language Packs

Challenge Packs

Submission Workflow

Draft

↓

Review

↓

Approval

↓

Marketplace

---

# Analytics

Track

Play Count

Completion Rate

Skip Rate

Favorite Count

Average Rating

Average Duration

Report Rate

Regional Popularity

---

# Card Scoring

Each card receives an internal quality score based on

Completion

Ratings

Reports

Replay Rate

AI Feedback

Editor Review

Low-performing cards are reviewed.

---

# A/B Testing

Test

Different Wording

Different Difficulty

Different Titles

Different Categories

Measure engagement before global rollout.

---

# AI Content Generation

Generate

Truths

Dares

Trivia

Corporate Games

Holiday Packs

Localization

Captions

AI never publishes directly.

---

# Media Assets

Cards may include

Emoji

Illustrations

Images

Audio

Voice

Animations

Future Video

---

# Accessibility

Cards support

Screen Readers

Large Text

Voice Narration

Simple Language

Translations

---

# Search

Search by

Category

Difficulty

Language

Pack

Tags

Popularity

Newest

---

# Audit Logs

Every change records

Editor

Action

Version

Timestamp

Reason

---

# Approval Workflow

```mermaid
flowchart TD

Author

↓

Draft

↓

Editor

↓

QA

↓

Localization

↓

Approval

↓

Publish
```

---

# Content Metrics Dashboard

Display

Total Cards

Published Packs

Most Played Cards

Lowest Rated Cards

Most Skipped Cards

Regional Trends

Creator Performance

---

# Security

Only authorized editors may

Publish

Archive

Delete Drafts

Create Packs

No published content may be edited directly.

---

# Future Features

AI Difficulty Balancing

Player-Generated Cards

Community Voting

Creator Revenue Sharing

Interactive Cards

AR Challenges

Location-Based Cards

Dynamic AI Cards

Live Event Packs

---

# Claude Code Instructions

When implementing content systems:

1. Never hard-delete published cards.
2. Use versioning for every edit.
3. AI-generated content requires human approval.
4. Track analytics for every card play.
5. Separate content from gameplay logic.
6. Support localization from the beginning.
7. Enforce moderation before publishing.
8. Update this document whenever new content types are introduced.

---

# Acceptance Criteria

The Content Pipeline is complete when:

- Every card follows a defined lifecycle.
- Packs organize content effectively.
- AI assists but does not publish autonomously.
- Content is versioned and localized.
- Analytics measure engagement.
- Creator content can be introduced safely.
- Moderation ensures quality and compliance.

---
