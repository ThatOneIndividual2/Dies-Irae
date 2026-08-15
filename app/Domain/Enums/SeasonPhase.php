<?php

namespace App\Domain\Enums;

final class SeasonPhase
{
    public const PLANTING = 'planting';
    public const GROWING = 'growing';
    public const HARVEST = 'harvest';
    public const WINTER = 'winter';

    public static function all(): array
    {
        return [self::PLANTING, self::GROWING, self::HARVEST, self::WINTER];
    }

    public static function fromMonth(int $month): string
    {
        $month = (($month - 1) % 12) + 1;

        return match (true) {
            $month >= 3 && $month <= 4 => self::PLANTING,
            $month >= 5 && $month <= 8 => self::GROWING,
            $month >= 9 && $month <= 10 => self::HARVEST,
            default => self::WINTER,
        };
    }

    public static function next(string $phase): string
    {
        return match ($phase) {
            self::PLANTING => self::GROWING,
            self::GROWING => self::HARVEST,
            self::HARVEST => self::WINTER,
            default => self::PLANTING,
        };
    }

    public static function startMonth(string $phase): int
    {
        return match ($phase) {
            self::PLANTING => 3,
            self::GROWING => 5,
            self::HARVEST => 9,
            default => 11,
        };
    }
}
