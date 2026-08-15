<?php

namespace App\Domain\Campaign;

final class PlayerArchetype
{
    public const KING = 'king';
    public const DUKE = 'duke';
    public const COUNT = 'count';
    public const MINOR_LORD = 'minor_lord';
    public const BISHOP = 'bishop';
    public const PRINCE_BISHOP = 'prince_bishop';
    public const HOLY_ORDER = 'holy_order';

    public static function all(): array
    {
        return [
            self::KING,
            self::DUKE,
            self::COUNT,
            self::MINOR_LORD,
            self::BISHOP,
            self::PRINCE_BISHOP,
            self::HOLY_ORDER,
        ];
    }

    public static function assertValid(string $archetype): void
    {
        if (!in_array($archetype, self::all(), true)) {
            throw new \InvalidArgumentException("Unknown player archetype: {$archetype}");
        }
    }
}
