<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\SponsorshipScope;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\PaySponsorshipInviteRequest;
use App\Http\Requests\Api\V1\StoreSponsorshipInviteRequest;
use App\Http\Resources\Api\V1\SponsorshipInviteResource;
use App\Models\Party;
use App\Services\Sponsorship\SponsorshipService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class SponsorshipInviteController extends Controller
{
    public function __construct(private readonly SponsorshipService $sponsorships) {}

    public function store(StoreSponsorshipInviteRequest $request, Party $party): JsonResponse
    {
        $this->authorize('createSponsorshipInvite', $party);

        $invite = $this->sponsorships->createInvite($party, $request->enum('scope', SponsorshipScope::class));

        return ApiResponse::success(new SponsorshipInviteResource($invite), 'Sponsorship invite created successfully.', 201);
    }

    /**
     * Open to any authenticated user — a valid token is its own
     * authorization, the same way a party's room code is.
     */
    public function show(string $token): JsonResponse
    {
        $invite = $this->sponsorships->findByToken($token);

        return ApiResponse::success(new SponsorshipInviteResource($invite), 'Sponsorship invite retrieved successfully.');
    }

    public function pay(PaySponsorshipInviteRequest $request, string $token): JsonResponse
    {
        $invite = $this->sponsorships->findByToken($token);

        $invite = $this->sponsorships->pay($request->user(), $invite, $request->validated('idempotency_key'));

        return ApiResponse::success(new SponsorshipInviteResource($invite), 'Sponsorship invite paid successfully.');
    }
}
