<?php

namespace App\Domain\Support;

final class BasisPoints
{
    public const FULL = 10000;

    public static function of(int $value, int $bp): int
    {
        if ($bp <= 0 || $value <= 0) {
            return 0;
        }

        return intdiv($value * $bp, self::FULL);
    }

    public static function complement(int $bp): int
    {
        return max(0, self::FULL - max(0, $bp));
    }

    public static function clamp(int $bp): int
    {
        if ($bp < 0) {
            return 0;
        }
        if ($bp > self::FULL) {
            return self::FULL;
        }

        return $bp;
    }

    public static function scale(int $bp, int $multiplierBp): int
    {
        return self::clamp(intdiv($bp * $multiplierBp, self::FULL));
    }
}
