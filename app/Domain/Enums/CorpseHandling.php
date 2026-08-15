<?php

namespace App\Domain\Enums;

final class CorpseHandling
{
    public const CONSECRATED = 'consecrated';
    public const MASS_GRAVE = 'mass_grave';
    public const BURNED = 'burned';
    public const ABANDONED = 'abandoned';
    public const DESECRATED = 'desecrated';

    public static function all(): array
    {
        return [
            self::CONSECRATED,
            self::MASS_GRAVE,
            self::BURNED,
            self::ABANDONED,
            self::DESECRATED,
        ];
    }

    /**
     * Extra infectiousness from the dead, in basis points added to the strain.
     */
    public static function infectiousnessBonus(string $handling): int
    {
        return match ($handling) {
            self::CONSECRATED => 0,
            self::MASS_GRAVE => 600,
            self::BURNED => 200,
            self::ABANDONED => 2200,
            self::DESECRATED => 3500,
            default => 800,
        };
    }

    /**
     * How quickly unburied dead are cleared per tick, in basis points of the pile.
     */
    public static function burialRate(string $handling): int
    {
        return match ($handling) {
            self::CONSECRATED => 4000,
            self::MASS_GRAVE => 7000,
            self::BURNED => 8500,
            self::ABANDONED => 500,
            self::DESECRATED => 300,
            default => 2000,
        };
    }

    public static function despairDelta(string $handling): int
    {
        return match ($handling) {
            self::CONSECRATED => -1,
            self::MASS_GRAVE => 1,
            self::BURNED => 2,
            self::ABANDONED => 6,
            self::DESECRATED => 10,
            default => 0,
        };
    }

    public static function corruptionDelta(string $handling): int
    {
        return match ($handling) {
            self::DESECRATED => 8,
            self::ABANDONED => 3,
            self::BURNED => 1,
            default => 0,
        };
    }
}
