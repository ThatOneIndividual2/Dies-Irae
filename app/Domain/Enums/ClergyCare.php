<?php

namespace App\Domain\Enums;

final class ClergyCare
{
    public const ABSENT = 'absent';
    public const NONE = 'none';
    public const PARISH = 'parish';
    public const HEROIC = 'heroic';

    public static function all(): array
    {
        return [
            self::ABSENT,
            self::NONE,
            self::PARISH,
            self::HEROIC,
        ];
    }

    /** Civilian infection reduction, basis points. */
    public static function civilianRelief(string $care): int
    {
        return match ($care) {
            self::PARISH => 1200,
            self::HEROIC => 2200,
            default => 0,
        };
    }

    /** Extra clergy class mortality weight while they tend the dying. */
    public static function clergyExposureWeight(string $care): int
    {
        return match ($care) {
            self::PARISH => 140,
            self::HEROIC => 190,
            self::ABSENT => 80,
            default => 100,
        };
    }

    /** Mortality reduction from last rites and isolation of the sick, basis points. */
    public static function mortalityRelief(string $care): int
    {
        return match ($care) {
            self::PARISH => 800,
            self::HEROIC => 1500,
            default => 0,
        };
    }

    public static function despairRelief(string $care): int
    {
        return match ($care) {
            self::PARISH => 2,
            self::HEROIC => 4,
            self::ABSENT => -3,
            default => 0,
        };
    }
}
