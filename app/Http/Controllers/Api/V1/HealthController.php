<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\Health\HealthCheckService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class HealthController extends Controller
{
    public function __construct(private readonly HealthCheckService $health) {}

    public function show(): JsonResponse
    {
        $checks = $this->health->check();

        $healthy = collect($checks)->doesntContain(fn (array $check) => $check['status'] === 'down');

        if (! $healthy) {
            return ApiResponse::error('One or more services are unhealthy.', ['checks' => $checks], 503);
        }

        return ApiResponse::success(['checks' => $checks], 'All services healthy.');
    }
}
