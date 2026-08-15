<?php

namespace App\Domain\Enums;

final class PenanceWorkType
{
    public const PRAYER = 'prayer';
    public const FASTING = 'fasting';
    public const ALMS = 'alms';
    public const PILGRIMAGE = 'pilgrimage';
    public const PUBLIC_SATISFACTION = 'public_satisfaction';

    public static function all(): array
    {
        return [self::PRAYER, self::FASTING, self::ALMS, self::PILGRIMAGE, self::PUBLIC_SATISFACTION];
    }
}
