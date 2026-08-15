<?php

namespace App\Domain\Support;

final class IntClamp
{
    public static function nonNegative(int $value): int
    {
        return $value < 0 ? 0 : $value;
    }

    public static function between(int $value, int $min, int $max): int
    {
        if ($value < $min) {
            return $min;
        }
        if ($value > $max) {
            return $max;
        }

        return $value;
    }
}
