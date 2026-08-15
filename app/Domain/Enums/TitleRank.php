<?php

namespace App\Domain\Enums;

final class TitleRank
{
    public const BARONY = 'barony';
    public const COUNTY = 'county';
    public const DUCHY = 'duchy';
    public const KINGDOM = 'kingdom';
    public const EMPIRE = 'empire';

    public static function all(): array
    {
        return [
            self::BARONY,
            self::COUNTY,
            self::DUCHY,
            self::KINGDOM,
            self::EMPIRE,
        ];
    }

    public static function weight(string $rank): int
    {
        return match ($rank) {
            self::EMPIRE => 500,
            self::KINGDOM => 400,
            self::DUCHY => 300,
            self::COUNTY => 200,
            self::BARONY => 100,
            default => 0,
        };
    }

    public static function isSecular(string $rank): bool
    {
        return in_array($rank, self::all(), true);
    }

    public static function assertSecular(string $rank): void
    {
        if (!self::isSecular($rank)) {
            throw new \InvalidArgumentException("Rank {$rank} is not a secular title rank.");
        }
    }
}
