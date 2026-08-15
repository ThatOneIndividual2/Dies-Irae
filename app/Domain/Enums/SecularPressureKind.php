<?php

namespace App\Domain\Enums;

final class SecularPressureKind
{
    public const DIPLOMACY = 'diplomacy';
    public const PATRONAGE = 'patronage';
    public const BRIBERY = 'bribery';
    public const THREAT = 'threat';
    public const MILITARY = 'military';
    public const ALLIANCE = 'alliance';
    public const RIVAL_CLAIMANT = 'rival_claimant';

    public static function all(): array
    {
        return [
            self::DIPLOMACY,
            self::PATRONAGE,
            self::BRIBERY,
            self::THREAT,
            self::MILITARY,
            self::ALLIANCE,
            self::RIVAL_CLAIMANT,
        ];
    }
}
