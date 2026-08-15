<?php

namespace App\Domain\Enums;

/**
 * Demographic and spiritual viability of a settlement.
 * Progression is not strictly linear: corrupted and overrun can branch
 * from any collapsed state when hell or despair takes the place.
 */
final class RuinState
{
    public const FUNCTIONING = 'functioning';
    public const STRAINED = 'strained';
    public const DEPOPULATED = 'depopulated';
    public const ABANDONED = 'abandoned';
    public const RUINED = 'ruined';
    public const CORRUPTED = 'corrupted';
    public const OVERRUN = 'overrun';

    public static function all(): array
    {
        return [
            self::FUNCTIONING,
            self::STRAINED,
            self::DEPOPULATED,
            self::ABANDONED,
            self::RUINED,
            self::CORRUPTED,
            self::OVERRUN,
        ];
    }

    public static function isKnown(string $state): bool
    {
        return in_array($state, self::all(), true);
    }

    public static function collectsTax(string $state): bool
    {
        return in_array($state, [self::FUNCTIONING, self::STRAINED, self::DEPOPULATED, self::CORRUPTED], true);
    }

    public static function raisesLevy(string $state): bool
    {
        return in_array($state, [self::FUNCTIONING, self::STRAINED, self::DEPOPULATED], true);
    }

    public static function parishFunctions(string $state): bool
    {
        return in_array($state, [self::FUNCTIONING, self::STRAINED, self::DEPOPULATED], true);
    }

    public static function producesFood(string $state): bool
    {
        return in_array($state, [self::FUNCTIONING, self::STRAINED, self::DEPOPULATED, self::CORRUPTED], true);
    }

    public static function acceptsRefugees(string $state): bool
    {
        return in_array($state, [self::FUNCTIONING, self::STRAINED, self::DEPOPULATED], true);
    }

    public static function isCollapsed(string $state): bool
    {
        return in_array($state, [self::ABANDONED, self::RUINED, self::CORRUPTED, self::OVERRUN], true);
    }
}
