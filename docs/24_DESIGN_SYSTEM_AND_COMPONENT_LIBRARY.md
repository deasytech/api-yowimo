# Yowimo Design System & Component Library

**Version:** 1.0.0

**Status:** Design Engineering Specification

**Priority:** CRITICAL

**Owner:** Product Design & Frontend Engineering

**Platform**

React Native

NativeWind

Expo

---

# Purpose

The Design System ensures every screen inside Yowimo feels like one product.

Every interaction should feel

Consistent

Beautiful

Fast

Accessible

Delightful

---

# Design Philosophy

Yowimo is not another social app.

It is a social entertainment platform.

The UI should communicate

Energy

Fun

Movement

Emotion

Celebration

without sacrificing usability.

---

# Design Principles

✓ Large touch targets

✓ Bold typography

✓ Strong gradients

✓ Glassmorphism accents

✓ Smooth animations

✓ Minimal cognitive load

✓ High accessibility

---

# Visual Identity

Brand Personality

Playful

Premium

Energetic

Modern

Social

Immersive

---

# Color Palette

## Primary

Yowimo Purple

```
#7A1EFF
```

---

Magenta

```
#D84CFF
```

---

Orange

```
#FF8A2A
```

---

Indigo

```
#3726A6
```

---

Background

```
#0D0D15
```

---

Surface

```
#181825
```

---

Border

```
#2D2D42
```

---

Success

```
#22C55E
```

---

Warning

```
#F59E0B
```

---

Danger

```
#EF4444
```

---

Info

```
#3B82F6
```

---

# Primary Gradient

```text
Purple

↓

Magenta

↓

Orange
```

Used for

Primary Buttons

Game Cards

Rewards

Highlights

Premium UI

---

# Glass Effect

Used sparingly.

Opacity

```
15%
```

Blur

```
Medium
```

Rounded Corners

```
24px
```

---

# Border Radius

Small

```
8
```

Medium

```
16
```

Large

```
24
```

Extra Large

```
32
```

Circle

```
9999
```

---

# Spacing Scale

```
4

8

12

16

20

24

32

40

48

64
```

Never use arbitrary spacing.

---

# Typography

Display

```
Bold

Extra Bold
```

Headings

```
Bold
```

Body

```
Regular
```

Captions

```
Medium
```

Buttons

```
SemiBold
```

---

# Font Scale

Hero

```
48
```

Display

```
36
```

H1

```
32
```

H2

```
28
```

H3

```
24
```

Title

```
20
```

Subtitle

```
18
```

Body

```
16
```

Caption

```
14
```

Small

```
12
```

Tiny

```
10
```

---

# Shadows

Small

Cards

Medium

Buttons

Large

Hero Cards

Glow

Rewards

MVP

Marketplace

---

# Icons

Primary

Lucide React Native

Emoji

Supported

Game Cards

Reactions

Awards

Highlights

---

# Button Components

## Primary Button

Gradient Background

White Text

Shadow

Rounded XL

Used for

Primary Actions

---

## Secondary Button

Surface Background

Border

Dark Text

Used for

Alternative Actions

---

## Ghost Button

Transparent

Text Only

Used for

Navigation

Dismiss

---

## Destructive Button

Red

Confirmation Required

---

# Input Components

Text Input

Password

OTP

Search

Textarea

Select

Date Picker

Tag Picker

---

Input States

Default

Focused

Disabled

Error

Success

---

# Avatar

Sizes

XS

SM

MD

LG

XL

Supports

Image

Gradient Initials

Status Badge

Online Indicator

Host Badge

Verified Badge

---

# Badge

Types

Success

Warning

Danger

Info

Premium

Sponsor

Host

VIP

---

# Chips

Used for

Categories

Filters

Tags

Difficulty

Languages

Party Types

---

# Cards

Party Card

Marketplace Card

Wallet Card

Game Card

Achievement Card

Reward Card

Sponsor Card

Player Card

---

# Progress Components

Linear

Circular

Countdown Ring

XP Bar

Level Progress

Party Progress

---

# Wallet Components

Balance Card

Transaction Item

Reward Popup

Purchase Sheet

Top Up Button

Sponsor Credit Card

---

# Navigation

Bottom Navigation

Floating FAB

Top Navigation

Back Button

Tab Bar

Segment Control

---

# Modals

Confirmation

Reward

Purchase

Invite

Profile

Bottom Sheet

Fullscreen

---

# Toasts

Success

Error

Warning

Info

Reward

Achievement

Sponsor

---

# Empty States

Every feature includes

Illustration

Headline

Description

Primary Action

---

# Loading States

Skeleton

Spinner

Shimmer

Progress

Countdown

---

# Animations

Use Reanimated.

Animation Library

Fade

Scale

Slide

Bounce

Pulse

Card Flip

Float

Celebrate

Confetti

---

# Motion Principles

Fast

```
150ms
```

Normal

```
250ms
```

Complex

```
400ms
```

Never exceed

```
600ms
```

---

# Celebration Effects

Confetti

Glow

Sparkles

Fireworks

Floating Emojis

Reward Burst

---

# Reactions

Support

🔥

😂

😍

😱

👏

❤️

💀

✨

Animated

Floating

Physics Based

---

# Accessibility

Minimum Touch Area

```
48x48
```

High Contrast

Supported

Dynamic Font

Supported

Reduced Motion

Supported

VoiceOver

Supported

---

# Screen Layout

```
Safe Area

↓

Header

↓

Hero

↓

Content

↓

Actions

↓

Bottom Safe Area
```

---

# Responsive Rules

Phone

Tablet

TV (Future)

Landscape

Portrait

---

# Component Naming

Examples

```
PrimaryButton

WalletCard

PartyCard

GameBadge

AvatarGroup

RewardToast

CountdownTimer

ReactionBar
```

---

# File Structure

```
components/

    buttons/

    cards/

    forms/

    feedback/

    layout/

    navigation/

    overlays/

    wallet/

    party/

    game/

    marketplace/

    profile/
```

---

# Design Tokens

Never hardcode

Colors

Radius

Spacing

Typography

Animations

Everything comes from the design system.

---

# Theme Support

Current

Dark

Future

Light

Corporate

Holiday

Halloween

Christmas

Neon

Minimal

---

# Claude Code Instructions

When generating UI:

1. Always use design tokens.
2. Never invent new spacing values.
3. Reuse existing components.
4. Prefer composition over duplication.
5. Use PrimaryButton instead of creating new button styles.
6. Keep animations subtle and performant.
7. Respect accessibility rules.
8. Update this document whenever new components are introduced.

---

# Acceptance Criteria

The Design System is complete when:

- Every screen is built from reusable components.
- Colors, typography, spacing, and motion are standardized.
- Components are accessible.
- New features can be built without creating inconsistent UI.
- Designers and developers share a common language.

---
