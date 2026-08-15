<?php

namespace App\Domain\Enums;

final class CorruptionSubjectType
{
    public const CHARACTER = 'character';
    public const HOUSEHOLD = 'household';
    public const SETTLEMENT = 'settlement';
    public const MONASTERY = 'monastery';
    public const ARMY = 'army';
    public const TERRITORY = 'territory';
    public const INSTITUTION = 'institution';

    public static function all(): array
    {
        return [
            self::CHARACTER,
            self::HOUSEHOLD,
            self::SETTLEMENT,
            self::MONASTERY,
            self::ARMY,
            self::TERRITORY,
            self::INSTITUTION,
        ];
    }
}
