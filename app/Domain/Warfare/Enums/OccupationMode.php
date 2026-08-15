<?php

namespace App\Domain\Warfare\Enums;

final class OccupationMode
{
    public const MILITARY_CONTROL = 'military_control';
    public const INFILTRATION = 'infiltration';
    public const HELL_OVERLAY = 'hell_overlay';
    public const CORRUPT_CONTROL = 'corrupt_control';
    public const COMMANDERY = 'commandery';

    public static function all(): array
    {
        return [
            self::MILITARY_CONTROL,
            self::INFILTRATION,
            self::HELL_OVERLAY,
            self::CORRUPT_CONTROL,
            self::COMMANDERY,
        ];
    }
}
