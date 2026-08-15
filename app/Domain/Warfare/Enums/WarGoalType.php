<?php

namespace App\Domain\Warfare\Enums;

final class WarGoalType
{
    public const CONQUEST = 'conquest';
    public const VASSALIZE = 'vassalize';
    public const SUPPRESS_CULT = 'suppress_cult';
    public const HOLY_WAR = 'holy_war';
    public const SEAL_RIFT = 'seal_rift';
    public const PURGE_CORRUPTION = 'purge_corruption';
    public const DEFENSIVE = 'defensive';

    public static function all(): array
    {
        return [
            self::CONQUEST,
            self::VASSALIZE,
            self::SUPPRESS_CULT,
            self::HOLY_WAR,
            self::SEAL_RIFT,
            self::PURGE_CORRUPTION,
            self::DEFENSIVE,
        ];
    }
}
