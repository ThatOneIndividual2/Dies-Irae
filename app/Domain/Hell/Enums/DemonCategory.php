<?php

namespace App\Domain\Hell\Enums;

/**
 * Catalog categories. The engine treats these as keys, not as a closed combat class tree.
 * New categories may be added in taxonomy.json without a code change if they only tag entities.
 */
final class DemonCategory
{
    public const LESSER = 'lesser';
    public const TEMPTER = 'tempter';
    public const POSSESSOR = 'possessor';
    public const COMMANDER = 'commander';
    public const PRINCE = 'prince';
    public const NAMED_UNIQUE = 'named_unique';

    public static function all(): array
    {
        return [
            self::LESSER,
            self::TEMPTER,
            self::POSSESSOR,
            self::COMMANDER,
            self::PRINCE,
            self::NAMED_UNIQUE,
        ];
    }

    public static function rankWeight(string $category): int
    {
        return match ($category) {
            self::NAMED_UNIQUE => 90,
            self::PRINCE => 80,
            self::COMMANDER => 55,
            self::POSSESSOR => 40,
            self::TEMPTER => 25,
            self::LESSER => 10,
            default => 0,
        };
    }
}
