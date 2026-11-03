<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Database\QueryException;
use Illuminate\Database\UniqueConstraintViolationException;

/**
 * Classifies database constraint errors across PostgreSQL and SQLite.
 */
final class ConstraintViolation
{
    public static function isUnique(QueryException $e): bool
    {
        return $e instanceof UniqueConstraintViolationException;
    }

    public static function isForeignKey(QueryException $e): bool
    {
        return (string) $e->getCode() === '23503'
            || str_contains(strtolower($e->getMessage()), 'foreign key');
    }
}
