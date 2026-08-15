<?php

namespace App\Domain\Enums;

final class CorruptionKind
{
    public const SPIRITUAL_ROT = 'spiritual_rot';
    public const INFERNAL_TAINT = 'infernal_taint';
    public const DESECRATION = 'desecration';
    public const DESPAIR_BLOOM = 'despair_bloom';
    public const SIMONY_STAIN = 'simony_stain';
    public const BLOODGUILT = 'bloodguilt';

    public static function all(): array
    {
        return [
            self::SPIRITUAL_ROT,
            self::INFERNAL_TAINT,
            self::DESECRATION,
            self::DESPAIR_BLOOM,
            self::SIMONY_STAIN,
            self::BLOODGUILT,
        ];
    }
}
