<?php

declare(strict_types=1);

use Illuminate\Database\ConnectionInterface;

it('reports a healthy application', function (): void {
    $this->getJson(route('health'))
        ->assertOk()
        ->assertExactJson(['status' => 'ok', 'database' => 'ok']);
});

it('reports degraded when the database is unreachable', function (): void {
    $connection = Mockery::mock(ConnectionInterface::class);
    $connection->shouldReceive('selectOne')->andThrow(new RuntimeException('connection refused'));
    $this->app->instance(ConnectionInterface::class, $connection);

    $this->getJson(route('health'))
        ->assertStatus(503)
        ->assertExactJson(['status' => 'degraded', 'database' => 'unavailable']);
});
