# Yowimo Game Engine Architecture

**Version:** 1.0.0

**Status:** Core Platform Specification

**Priority:** CRITICAL

**Owner:** Gameplay Engineering

**Depends On**

- 00_READ_ME_FIRST.md
- 03_DOMAIN_MODEL.md
- 04_DATABASE_ARCHITECTURE.md
- 05_API_STANDARDS.md
- 07_EVENT_CATALOG.md

---

# Purpose

The Game Engine is the heart of Yowimo.

Everything that happens during gameplay is orchestrated by the Game Engine.

This document defines:

- Party flow
- Round flow
- Turn flow
- Card selection
- Timer management
- AI Host behavior
- Voting
- Rewards
- Reactions
- Spectator interaction
- Reconnection
- Game recovery
- Fairness
- Anti-cheat

This document is the single source of truth for gameplay.

---

# Gameplay Philosophy

Yowimo is **not** simply a card game.

It is a real-time multiplayer social experience.

Every decision should optimize for:

✓ Fun

✓ Fairness

✓ Speed

✓ Social interaction

✓ Replayability

✓ Spectator engagement

✓ Virality

---

# High-Level Game Flow

```text
Create Party

↓

Invite Players

↓

Players Join

↓

Ready Check

↓

Host Starts Game

↓

Game Session Created

↓

Rounds Begin

↓

Turns Execute

↓

Cards Revealed

↓

Challenges Completed

↓

Votes

↓

Rewards

↓

Next Round

↓

Game Ends

↓

Awards

↓

Highlights

↓

Recap

↓

Party Ends
```

---

# Game Lifecycle

```mermaid
stateDiagram-v2

Waiting

-->Lobby

Lobby

-->Ready

Ready

-->Countdown

Countdown

-->Running

Running

-->Paused

Paused

-->Running

Running

-->Completed

Completed

-->Archived
```

---

# Party States

Created

Scheduled

Open

Waiting

Ready

Starting

Live

Paused

Completed

Archived

---

# Game Session

One Party owns one Game Session.

A Game Session owns:

Rounds

Turn Order

Cards

Timers

Votes

Rewards

Statistics

---

Game Session Fields

```text
id

party_id

game_type

difficulty

language

host_id

started_at

ended_at

status
```

---

# Round Engine

Every game consists of one or more rounds.

Default

```
10 Rounds
```

Host may configure

5

10

15

20

Unlimited (Future)

---

Round Lifecycle

Pending

↓

Preparing

↓

Running

↓

Voting

↓

Completed

---

Each Round Owns

Turn Queue

Timer

Card History

Winner

Statistics

---

# Turn Engine

Each player gets exactly one turn before the cycle repeats.

Turn States

Waiting

↓

Preparing

↓

Active

↓

Voting

↓

Completed

↓

Archived

---

Turn Data

```text
Player

Card

Challenge

Timer

Votes

Result

Tokens Earned

Reactions
```

---

# Turn Order

Default

Randomized once.

Example

```
Alex

↓

Maya

↓

Leo

↓

Sam

↓

Alex
```

Host Options

Random

Clockwise

Manual

Team Rotation

---

# Card Engine

The Card Engine selects cards.

Rules

Never repeat recently played cards.

Avoid duplicate categories.

Respect age rating.

Respect difficulty.

Respect game mode.

Respect purchased card packs.

---

Card Selection Flow

```text
Player Turn

↓

Eligible Packs

↓

Eligible Cards

↓

Random Weight

↓

Difficulty Filter

↓

History Filter

↓

Final Card
```

---

# Card Types

Truth

Dare

Never Have I Ever

Most Likely To

Would You Rather

Trivia

Hot Seat

Icebreaker

Corporate

Team Challenge

Creator Challenge

Sponsored Challenge

AI Generated

---

# Difficulty Levels

Easy

Medium

Hard

Extreme

Host Configurable

---

# Timer Engine

Default Turn

```
45 Seconds
```

Host Settings

15

30

45

60

90

Unlimited

---

Timer States

Ready

↓

Running

↓

Warning

↓

Expired

↓

Completed

---

Warning Threshold

Default

10 Seconds

AI Host announces

"Hurry up!"

---

# AI Host

The AI Host controls narration.

Responsibilities

Welcome Players

Explain Rules

Introduce Rounds

Read Cards

Count Down

React

Announce Winners

Generate Highlights

Generate Recap

---

Voice Modes

Friendly

Funny

Energetic

Corporate

Romantic

Family

Future Celebrity Voices

---

# Reaction Engine

Players may react during turns.

Supported

🔥

😂

❤️

😱

👏

💀

🤯

👀

✨

Unlimited future emojis.

Reactions do not interrupt gameplay.

---

# Audience Engine

Audience may

React

Comment

Vote

Watch

Never interfere with turn ownership.

---

# Spectator Mode

Spectators may

Watch

React

Comment

Vote (Optional)

Cannot

Play Cards

Earn Tokens

Modify Game

---

# Voting Engine

Some games require voting.

Vote Types

Winner

Loser

Most Funny

Most Creative

Best Actor

Skip

Penalty

---

Voting Flow

Challenge Ends

↓

Vote Opens

↓

Players Vote

↓

Vote Closes

↓

Results Calculated

↓

Rewards Granted

---

Voting Rules

One vote per player.

Cannot vote twice.

Cannot vote after closing.

Server validates.

---

# Team Mode

Players assigned into teams.

Turn Order

Team A

↓

Team B

↓

Team A

↓

Team B

Team Score maintained separately.

---

# Hybrid Mode

Supports

In-person Players

-

Remote Players

-

Audience

All synchronized through Reverb.

---

# Corporate Mode

Differences

Professional cards

HR-safe content

Icebreakers

Training cards

Voting disabled (optional)

AI Host uses professional tone.

---

# Sponsored Mode

Sponsor may

Pay Entry

Reward Winners

Unlock Packs

Display Branding

Cannot influence gameplay.

---

# Local Pass Mode

One phone.

Many players.

Flow

Pass Device

↓

Register Player

↓

Next Player

↓

Continue

---

# QR Join

Host displays QR.

Player scans.

↓

Join Party.

↓

Ready.

---

# TV Mode

One display.

Many phones.

TV shows

Current Player

Timer

Card

Leaderboard

Highlights

Phones remain controllers.

---

# Live Video Mode

Uses LiveKit.

Supports

Video

Voice

Screen Sharing (Future)

Recording

Background Blur (Future)

---

# Pause Logic

Game pauses when

Host pauses.

Server outage.

LiveKit reconnect.

Critical error.

---

Resume restores

Timer

Turn

Votes

Card

AI State

---

# Reconnection Logic

Player disconnects

↓

Grace Period

30 seconds

↓

Reconnect

↓

Restore Session

↓

Continue

If timeout

↓

AI Host continues

---

# AFK Detection

Inactive for

90 seconds

↓

Warn

↓

Auto Skip

↓

Penalty (Optional)

---

# Skip Rules

Host Skip

Player Skip

Timeout Skip

Sponsor Skip (Disabled)

---

Penalty

Lose Tokens

Lose Score

Lose Vote

Host Configurable

---

# Reward Engine

Rewards granted for

Completion

Winning

Creativity

Voting

MVP

Daily Streak

Sponsors

Advertisements

Achievements

---

Reward Types

Tokens

XP

Badges

Marketplace Unlocks

Titles

Leaderboards

---

# Scoring Engine

Example

Challenge Completed

+50

Winner Vote

+25

Funny Vote

+15

Creativity

+20

MVP

+100

---

# Combo System (Future)

Complete consecutive challenges

↓

Combo Multiplier

↓

Higher Rewards

---

# Achievement Engine

Examples

First Party

100 Parties

Perfect Game

No Skips

Party King

Truth Master

Dare Devil

Social Butterfly

---

# Anti-Cheat

Server owns

Timer

Cards

Scores

Votes

Rewards

Turn Order

Client cannot modify.

---

# Failure Recovery

Unexpected Server Restart

↓

Restore

Game

Round

Turn

Timer

Votes

Chat

AI State

---

# Analytics

Track

Game Duration

Completion Rate

Skipped Cards

Popular Packs

Reaction Count

Average Turn Time

Most Played Mode

Retention

---

# Events

Game Started

Round Started

Turn Started

Card Revealed

Challenge Completed

Vote Opened

Vote Closed

Reward Granted

Game Completed

Awards Generated

Highlights Generated

---

# Future Features

Tournament Mode

Season Pass

Battle Pass

Ranked Mode

Guilds

Clans

Streamer Mode

Creator Packs

Community Cards

AI Generated Cards

Voice Commands

AR Mode

VR Mode

---

# Claude Code Instructions

When implementing gameplay:

1. Server owns game state.
2. Client never determines winners.
3. Timer always runs on the server.
4. Every transition emits an event.
5. Persist state before broadcasting.
6. AI Host reacts to events rather than controlling game flow directly.
7. Never block gameplay while analytics or notifications execute.
8. Maintain deterministic state recovery after failures.
9. Keep all game rules configurable through database settings where appropriate.

---

# Acceptance Criteria

The Game Engine is considered complete when:

- Every gameplay state is deterministic.
- Players can disconnect and reconnect safely.
- Timers remain authoritative.
- Cards are selected fairly.
- Rewards are granted consistently.
- Spectators and audience interactions are isolated from gameplay.
- AI Host integrates through events.
- Team, Local, Hybrid, Corporate, and Sponsored modes share the same engine with configuration rather than duplicate logic.

---
