<?php

namespace App\Domain\Enums;

final class ApocalypseStage
{
    public const ORDINARY_ORDER = 'ordinary_order';
    public const GREAT_MORTALITY = 'great_mortality';
    public const THINNING_VEIL = 'thinning_veil';
    public const OPEN_RIFTS = 'open_rifts';
    public const COLLAPSE_OF_OFFICES = 'collapse_of_offices';
    public const END_OF_THE_AGE = 'end_of_the_age';

    public static function all(): array
    {
        return [
            self::ORDINARY_ORDER,
            self::GREAT_MORTALITY,
            self::THINNING_VEIL,
            self::OPEN_RIFTS,
            self::COLLAPSE_OF_OFFICES,
            self::END_OF_THE_AGE,
        ];
    }

    public static function weight(string $stage): int
    {
        $i = array_search($stage, self::all(), true);

        return $i === false ? 0 : (int) $i;
    }

    public static function atLeast(string $current, string $required): bool
    {
        return self::weight($current) >= self::weight($required);
    }
}
