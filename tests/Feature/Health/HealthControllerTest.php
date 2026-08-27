<?php

use App\Services\Health\HealthCheckService;

it('is publicly accessible without authentication and returns overall health', function () {
    $response = $this->getJson('/api/v1/health');

    $response->assertOk();
    $response->assertJson([
        'success' => true,
    ]);
    $response->assertJsonStructure([
        'success',
        'message',
        'data' => [
            'checks' => ['database', 'redis', 'queue', 'broadcast'],
        ],
    ]);
});

it('returns a 503 when a dependency is unhealthy', function () {
    $this->mock(HealthCheckService::class)
        ->shouldReceive('check')
        ->once()
        ->andReturn([
            'database' => ['status' => 'down', 'message' => 'connection refused'],
            'redis' => ['status' => 'ok'],
            'queue' => ['status' => 'ok'],
            'broadcast' => ['status' => 'skipped'],
        ]);

    $response = $this->getJson('/api/v1/health');

    $response->assertStatus(503);
    $response->assertJson([
        'success' => false,
    ]);
});
