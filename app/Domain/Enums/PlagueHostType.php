<?php

namespace App\Domain\Enums;

final class PlagueHostType
{
    public const SETTLEMENT = 'settlement';
    public const ARMY = 'army';
    public const CARAVAN = 'caravan';
    public const PILGRIM_COLUMN = 'pilgrim_column';

    public static function all(): array
    {
        return [
            self::SETTLEMENT,
            self::ARMY,
            self::CARAVAN,
            self::PILGRIM_COLUMN,
        ];
    }

    public static function isMobile(string $type): bool
    {
        return $type !== self::SETTLEMENT;
    }
}
