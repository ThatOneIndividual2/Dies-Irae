<?php

namespace App\Domain\Enums;

final class HolyOrderVowType
{
    public const POVERTY = 'poverty';
    public const CHASTITY = 'chastity';
    public const OBEDIENCE = 'obedience';
    public const HOSPITALITY = 'hospitality';

    public static function all(): array
    {
        return [self::POVERTY, self::CHASTITY, self::OBEDIENCE, self::HOSPITALITY];
    }

    public static function militaryRule(): array
    {
        return [self::POVERTY, self::CHASTITY, self::OBEDIENCE];
    }
}
