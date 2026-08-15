<?php

namespace App\Domain\Enums;

final class SpiritualOfficeRank
{
    public const DEACON = 'deacon';
    public const PRIEST = 'priest';
    public const PARISH_PRIEST = 'priest';
    public const ABBOT = 'abbot';
    public const PRIOR = 'prior';
    public const BISHOP = 'bishop';
    public const ARCHBISHOP = 'archbishop';
    public const CARDINAL = 'cardinal';
    public const POPE = 'pope';
    public const LEGATE = 'legate';
    public const GRAND_MASTER = 'grand_master';
    public const MASTER = 'master';

    public static function all(): array
    {
        return [
            self::DEACON,
            self::PRIEST,
            self::ABBOT,
            self::PRIOR,
            self::BISHOP,
            self::ARCHBISHOP,
            self::CARDINAL,
            self::POPE,
            self::LEGATE,
            self::GRAND_MASTER,
            self::MASTER,
        ];
    }

    public static function isSpiritual(string $rank): bool
    {
        return in_array($rank, self::all(), true);
    }

    public static function weight(string $rank): int
    {
        return match ($rank) {
            self::POPE => 900,
            self::CARDINAL => 800,
            self::LEGATE => 750,
            self::ARCHBISHOP => 700,
            self::BISHOP => 600,
            self::ABBOT => 500,
            self::GRAND_MASTER => 480,
            self::MASTER => 450,
            self::PRIOR => 400,
            self::PRIEST => 300,
            self::DEACON => 200,
            default => 0,
        };
    }
}
