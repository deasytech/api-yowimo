<?php

namespace App\Services\Health;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Redis;
use Throwable;

class HealthCheckService
{
    /**
     * @return array<string, array{status: string, message?: string}>
     */
    public function check(): array
    {
        return [
            'database' => $this->checkDatabase(),
            'redis' => $this->checkRedis(),
            'queue' => $this->checkQueue(),
            'broadcast' => $this->checkBroadcast(),
        ];
    }

    /**
     * @return array{status: string, message?: string}
     */
    private function checkDatabase(): array
    {
        try {
            DB::connection()->getPdo();

            return ['status' => 'ok'];
        } catch (Throwable $e) {
            return ['status' => 'down', 'message' => $e->getMessage()];
        }
    }

    /**
     * @return array{status: string, message?: string}
     */
    private function checkRedis(): array
    {
        try {
            Redis::connection()->ping();

            return ['status' => 'ok'];
        } catch (Throwable $e) {
            return ['status' => 'down', 'message' => $e->getMessage()];
        }
    }

    /**
     * @return array{status: string, message?: string}
     */
    private function checkQueue(): array
    {
        try {
            Queue::connection()->size();

            return ['status' => 'ok'];
        } catch (Throwable $e) {
            return ['status' => 'down', 'message' => $e->getMessage()];
        }
    }

    /**
     * @return array{status: string, message?: string}
     */
    private function checkBroadcast(): array
    {
        $default = config('broadcasting.default');

        if ($default !== 'reverb') {
            return ['status' => 'skipped', 'message' => "broadcast driver is \"{$default}\", not reverb"];
        }

        $host = config('broadcasting.connections.reverb.options.host');
        $port = (int) config('broadcasting.connections.reverb.options.port');

        $connection = @fsockopen($host, $port, $errno, $errstr, timeout: 1);

        if (! $connection) {
            return ['status' => 'down', 'message' => $errstr !== '' ? $errstr : 'connection failed'];
        }

        fclose($connection);

        return ['status' => 'ok'];
    }
}
