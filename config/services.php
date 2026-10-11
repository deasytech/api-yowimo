<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'clerk' => [
        'issuer' => env('CLERK_ISSUER'),
        'jwks_url' => env('CLERK_JWKS_URL'),
        'jwks_cache_ttl' => env('CLERK_JWKS_CACHE_TTL', 3600),
        'webhook_secret' => env('CLERK_WEBHOOK_SECRET'),
        'secret_key' => env('CLERK_SECRET_KEY'),
    ],

    'openai' => [
        'api_key' => env('OPENAI_API_KEY'),
        'model' => env('OPENAI_MODEL', 'gpt-4o-mini'),
    ],

    'paystack' => [
        'secret_key' => env('PAYSTACK_SECRET_KEY'),
        'public_key' => env('PAYSTACK_PUBLIC_KEY'),
        'base_url' => env('PAYSTACK_BASE_URL', 'https://api.paystack.co'),
    ],

    'livekit' => [
        'api_key' => env('LIVEKIT_API_KEY'),
        'api_secret' => env('LIVEKIT_API_SECRET'),
        'url' => env('LIVEKIT_URL'),
    ],

    'admob' => [
        // Confirm against https://developers.google.com/admob/android/ssv
        // at implementation/deploy time — Google documents this as the
        // current SSV public-keys URL but has moved it before.
        'ssv_keys_url' => env('ADMOB_SSV_KEYS_URL', 'https://gstatic.com/admob/reward/verifier-keys.json'),
        'ssv_keys_cache_ttl' => env('ADMOB_SSV_KEYS_CACHE_TTL', 43200),

        // Business rules for the rewarded-ad token quest — backend-owned so
        // the daily limit/payout can be tuned without a mobile release, and
        // never hard-coded into the client.
        'rewarded_ads_enabled' => env('ADMOB_REWARDED_ADS_ENABLED', true),
        'daily_token_limit' => env('ADMOB_REWARDED_ADS_DAILY_LIMIT', 15),
        'tokens_per_completed_ad' => env('ADMOB_REWARDED_ADS_TOKENS_PER_AD', 1),
    ],

    'referrals' => [
        // Backend-owned, same pattern as the ad-reward token quest above —
        // tunable without a mobile release, never hard-coded into the client.
        'reward_amount' => env('REFERRAL_REWARD_AMOUNT', 20),
    ],

    'sponsorship' => [
        // Base for the sponsor invite link returned in SponsorshipInviteResource
        // (https://yowimo.app/s/{token}) — until universal links are set up this
        // won't open the app, but the token itself still resolves via
        // GET /sponsorship-invites/{token} either way.
        'web_url' => env('SPONSORSHIP_WEB_URL', 'https://yowimo.app/s'),
        'invite_ttl_hours' => env('SPONSORSHIP_INVITE_TTL_HOURS', 72),
    ],

];
