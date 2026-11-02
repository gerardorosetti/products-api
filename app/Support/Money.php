<?php

declare(strict_types=1);

namespace App\Support;

use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;

final class Money
{
    /**
     * Formats a decimal string with exactly two decimals (half-up rounding).
     */
    public static function format(string $amount): string
    {
        return (string) BigDecimal::of($amount)->toScale(2, RoundingMode::HalfUp);
    }
}
