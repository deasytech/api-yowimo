# Yowimo Marketplace Architecture

**Version:** 1.0.0

**Status:** Core Business Specification

**Priority:** HIGH

**Owner:** Commerce Platform Team

**Depends On**

- 03_DOMAIN_MODEL.md
- 04_DATABASE_ARCHITECTURE.md
- 05_API_STANDARDS.md
- 07_EVENT_CATALOG.md
- 10_QUEUE_AND_BACKGROUND_JOBS.md
- 12_WALLET_AND_TOKEN_SYSTEM.md

---

# Purpose

The Marketplace is the digital commerce platform powering Yowimo.

It enables players to discover, purchase, unlock, and use premium content while providing sustainable monetization for the platform.

The Marketplace must remain:

✓ Fast

✓ Fair

✓ Secure

✓ Extensible

✓ Creator Ready

✓ Mobile First

---

# Marketplace Vision

Yowimo is not simply selling card packs.

The Marketplace is designed to become an entertainment ecosystem where players purchase experiences rather than products.

Examples

Premium Game Packs

AI Voices

Seasonal Content

Themes

Animations

Corporate Packs

Creator Packs

Tournament Tickets

Future Digital Goods

---

# Marketplace Architecture

```text
Marketplace

↓

Catalog

↓

Products

↓

Pricing

↓

Purchases

↓

Delivery

↓

Ownership

↓

Inventory
```

---

# Marketplace Modules

Catalog

Product Management

Bundles

Discounts

Purchases

Inventory

Recommendations

Promotions

Featured Content

Analytics

---

# Product Types

Current

Card Packs

AI Voice Packs

Themes

Premium Challenges

Animation Packs

Token Bundles

Future

Creator Packs

Subscriptions

Season Pass

Battle Pass

Avatar Cosmetics

Profile Frames

Party Effects

Sticker Packs

Music Packs

Corporate Templates

---

# Product Entity

Fields

```
id

name

slug

description

type

price

currency

status

visibility

thumbnail

cover_image

metadata

created_at
```

---

# Product Status

Draft

Published

Hidden

Scheduled

Archived

Discontinued

---

# Product Visibility

Public

Private

Regional

Sponsor Only

Corporate Only

Premium Only

---

# Categories

Truth or Dare

Never Have I Ever

Corporate

Family

Romantic

Spicy

Holiday

Funny

Educational

Creator

AI

Themes

Audio

---

# Card Packs

Every Card Pack contains

Cards

Difficulty

Age Rating

Category

Language

Tags

Artwork

Version

---

Example

```
Midnight Spice

120 Cards

18+

Difficulty

Hard
```

---

# Product Bundles

Bundle Examples

Starter Bundle

Premium Bundle

Couples Bundle

Office Bundle

Family Bundle

Holiday Bundle

Launch Bundle

---

Bundles may include

Card Packs

Themes

Voice Packs

Tokens

Animations

---

# Pricing

Current Currency

Tokens

Future

Local Currency

Corporate Pricing

Subscription Credits

Regional Pricing

---

# Regional Pricing

Future support

Nigeria

United States

Brazil

United Kingdom

Canada

Europe

Middle East

Asia

Each region may define

Price

Currency

Discount

Taxes

---

# Discounts

Discount Types

Percentage

Fixed Amount

Free Product

Buy One Get One

Seasonal

Referral

Sponsor

Corporate

---

# Coupon System

Coupon Fields

Code

Type

Value

Start Date

End Date

Usage Limit

Per User Limit

Applicable Products

---

Example

```
WELCOME20

↓

20% Off

↓

Expires

30 Days
```

---

# Ownership

Purchasing creates ownership.

Ownership Fields

```
user_id

product_id

purchased_at

expires_at

source
```

Ownership is permanent unless explicitly revoked.

---

# Inventory

Players own

Purchased Packs

Unlocked Voices

Themes

Cosmetics

Achievements

Future Assets

Inventory determines available content during gameplay.

---

# Purchase Flow

```mermaid
flowchart TD

Browse

↓

View Product

↓

Purchase

↓

Wallet Validation

↓

Transaction

↓

Ledger

↓

Grant Ownership

↓

Inventory Updated

↓

Notification
```

---

# Delivery

Digital products deliver immediately.

Steps

Validate Purchase

Grant Ownership

Refresh Inventory

Emit Events

Notify User

---

# Refunds

Refund Flow

Refund Approved

↓

Revoke Ownership

↓

Credit Wallet

↓

Ledger Entry

↓

Audit Log

---

# Featured Products

Admin can feature products.

Examples

Trending

New

Limited

Seasonal

Editor's Choice

AI Recommended

---

# Recommendations

Marketplace recommends products using

Purchase History

Game History

Party Type

Friends

Trending

Season

Region

AI Suggestions

---

# Seasonal Content

Examples

Christmas

Halloween

Valentine

New Year

Easter

Ramadan

World Cup

Back To School

---

Seasonal products activate automatically based on schedule.

---

# Limited-Time Products

Fields

Launch Date

End Date

Purchase Limit

Region

Countdown

---

# Creator Marketplace (Future)

Creators can publish

Card Packs

Themes

Voice Packs

Trivia Packs

Corporate Packs

Language Packs

Future

Mini Games

---

Creator Revenue

Future Revenue Share

```
70%

Creator

30%

Yowimo
```

---

# AI Products

Future Marketplace

Premium Narrators

Celebrity Voices

Story Packs

AI Dungeon Hosts

Personalized Challenges

Voice Cloning (Optional)

---

# Product Assets

Every product includes

Thumbnail

Banner

Preview

Description

Screenshots

Preview Video

Metadata

---

# Search

Supports

Full Text

Category

Difficulty

Age Rating

Price

Language

Popularity

Newest

---

# Filters

Newest

Popular

Highest Rated

Trending

Free

Premium

Owned

Not Owned

---

# Ratings

Players can rate

Card Packs

Themes

Voices

Bundles

Ratings influence recommendations.

---

# Reviews

Future

Verified Purchase Only

Helpful Votes

Report Abuse

AI Summary

---

# Product Analytics

Track

Views

Purchases

Conversion Rate

Revenue

Refund Rate

Retention

Average Rating

Time to Purchase

---

# Inventory Sync

When ownership changes

↓

Refresh Inventory Cache

↓

Broadcast Update

↓

Client Refresh

---

# Offline Support

Owned products cached locally.

Purchases require online validation.

---

# Security

Validate

Ownership

Duplicate Purchases

Wallet Balance

Product Availability

Region

Version Compatibility

---

# Compliance

Google Play

Apple App Store

Digital Goods Policy

Regional Tax Rules

Privacy Laws

Consumer Refund Laws

---

# Events

```
product.created

product.updated

product.published

purchase.started

purchase.completed

purchase.refunded

inventory.updated

bundle.redeemed
```

---

# Monitoring

Track

Revenue

Conversion

Purchase Failures

Refunds

Popular Products

Trending Categories

Inventory Errors

---

# Future Features

Subscriptions

Gift Purchases

Wishlists

Flash Sales

Auctions

Mystery Packs

Loot Crates (Regional Compliance)

Corporate Licenses

Creator Dashboard

Affiliate Marketplace

---

# Claude Code Instructions

When implementing Marketplace features:

1. Never grant ownership before successful payment.
2. Validate purchases through WalletService.
3. Record every purchase as a transaction.
4. Update inventory only after purchase completion.
5. Emit Marketplace events.
6. Cache catalog queries.
7. Support future product types through extensible metadata.
8. Update this document when introducing new product categories or commerce features.

---

# Acceptance Criteria

The Marketplace is complete when:

- Products are organized into a searchable catalog.
- Ownership is tracked independently of purchases.
- Purchases are transactional and auditable.
- Inventory reflects owned products accurately.
- Seasonal and promotional content is supported.
- Creator Marketplace can be added without redesign.
- Regional pricing and compliance are accommodated.

---
