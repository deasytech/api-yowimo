<?php

namespace App\Http\Controllers\Api\V1;

use App\Exceptions\Api\InvalidAdMobSsvSignatureException;
use App\Http\Controllers\Controller;
use App\Services\Ads\AdMobSsvVerifier;
use App\Services\Ads\AdRewardService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * AdMob calls this with GET, everything in the query string — unlike
 * PaystackWebhookController's POST+JSON body.
 *
 * Always responds 200 once the signature itself is valid — an unknown,
 * already-resolved, expired, or capped-out session is still a 200 (so
 * Google stops retrying a callback there's nothing more to do with); only a
 * bad signature gets a non-200.
 */
class AdRewardWebhookController extends Controller
{
    public function __construct(
        private readonly AdMobSsvVerifier $verifier,
        private readonly AdRewardService $adRewards,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $verifiedParams = $this->verifier->verify($request);

        if ($verifiedParams === null) {
            // A forged/tampered callback is the most security-sensitive
            // failure in this whole flow — worth a log line to spot an abuse
            // pattern. Never the query string itself: customData is this
            // flow's bearer credential and reward_amount/transaction_id are
            // still unverified at this point, so none of it is trustworthy
            // to record as fact.
            Log::warning('Rewarded ad SSV callback rejected: invalid signature.', [
                'ip' => $request->ip(),
                'key_id' => $request->query('key_id'),
            ]);

            throw new InvalidAdMobSsvSignatureException;
        }

        $this->adRewards->verifyAndCredit($verifiedParams);

        return ApiResponse::success(message: 'Webhook processed.');
    }
}
