<?php

use App\Services\Health\HealthCheckService;

beforeEach(function () {
    $this->originalConfig = [
        'database.default' => config('database.default'),
        'database.redis.default.host' => config('database.redis.default.host'),
        'database.redis.default.port' => config('database.redis.default.port'),
        'queue.default' => config('queue.default'),
        'broadcasting.default' => config('broadcasting.default'),
        'broadcasting.connections.reverb.options.host' => config('broadcasting.connections.reverb.options.host'),
        'broadcasting.connections.reverb.options.port' => config('broadcasting.connections.reverb.options.port'),
    ];
});

afterEach(function () {
    config($this->originalConfig);
});

it('reports database, redis, and queue as ok when infrastructure is reachable', function () {
    $result = app(HealthCheckService::class)->check();

    expect($result['database']['status'])->toBe('ok');
    expect($result['redis']['status'])->toBe('ok');
    expect($result['queue']['status'])->toBe('ok');
});

it('reports database as down when the connection is invalid', function () {
    config(['database.default' => 'not-a-real-connection']);

    $result = app(HealthCheckService::class)->check();

    config($this->originalConfig);

    expect($result['database']['status'])->toBe('down');
    expect($result['database']['message'])->not->toBeEmpty();
});

it('reports redis as down when unreachable', function () {
    config([
        'database.redis.default.host' => '127.0.0.1',
        'database.redis.default.port' => 1,
    ]);

    $result = app(HealthCheckService::class)->check();

    config($this->originalConfig);

    expect($result['redis']['status'])->toBe('down');
    expect($result['redis']['message'])->not->toBeEmpty();
});

it('reports queue as down when the connection is invalid', function () {
    config(['queue.default' => 'not-a-real-connection']);

    $result = app(HealthCheckService::class)->check();

    config($this->originalConfig);

    expect($result['queue']['status'])->toBe('down');
    expect($result['queue']['message'])->not->toBeEmpty();
});

it('skips the broadcast check when the default driver is not reverb', function () {
    config(['broadcasting.default' => 'null']);

    $result = app(HealthCheckService::class)->check();

    config($this->originalConfig);

    expect($result['broadcast']['status'])->toBe('skipped');
});

it('reports broadcast as ok when reverb is reachable', function () {
    $server = stream_socket_server('tcp://127.0.0.1:0');
    $address = stream_socket_get_name($server, false);
    $port = (int) explode(':', $address)[1];

    config([
        'broadcasting.default' => 'reverb',
        'broadcasting.connections.reverb.options.host' => '127.0.0.1',
        'broadcasting.connections.reverb.options.port' => $port,
    ]);

    $result = app(HealthCheckService::class)->check();

    fclose($server);
    config($this->originalConfig);

    expect($result['broadcast']['status'])->toBe('ok');
});

it('reports broadcast as down when reverb is unreachable', function () {
    config([
        'broadcasting.default' => 'reverb',
        'broadcasting.connections.reverb.options.host' => '127.0.0.1',
        'broadcasting.connections.reverb.options.port' => 1,
    ]);

    $result = app(HealthCheckService::class)->check();

    config($this->originalConfig);

    expect($result['broadcast']['status'])->toBe('down');
});
