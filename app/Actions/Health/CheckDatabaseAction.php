<?php

declare(strict_types=1);

namespace App\Actions\Health;

use Illuminate\Database\ConnectionInterface;
use Throwable;

final readonly class CheckDatabaseAction
{
    public function __construct(private ConnectionInterface $connection) {}

    public function handle(): bool
    {
        try {
            $this->connection->selectOne('select 1');

            return true;
        } catch (Throwable) {
            return false;
        }
    }
}
