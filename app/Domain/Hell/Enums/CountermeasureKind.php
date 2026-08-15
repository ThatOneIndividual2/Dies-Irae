<?php

namespace App\Domain\Hell\Enums;

final class CountermeasureKind
{
    public const EXORCISM = 'exorcism';
    public const RELIC_USE = 'relic_use';
    public const PRAYER = 'prayer';
    public const CONSECRATION = 'consecration';
    public const PILGRIMAGE = 'pilgrimage';
    public const MILITARY_CLEANSING = 'military_cleansing';
    public const DESTROY_CULT = 'destroy_cult';
    public const CLOSE_BREACH = 'close_breach';
    public const MARTYRDOM = 'martyrdom';
    public const HOLY_ORDER = 'holy_order';

    public static function all(): array
    {
        return [
            self::EXORCISM,
            self::RELIC_USE,
            self::PRAYER,
            self::CONSECRATION,
            self::PILGRIMAGE,
            self::MILITARY_CLEANSING,
            self::DESTROY_CULT,
            self::CLOSE_BREACH,
            self::MARTYRDOM,
            self::HOLY_ORDER,
        ];
    }
}
