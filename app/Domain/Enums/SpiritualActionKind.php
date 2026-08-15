<?php

namespace App\Domain\Enums;

final class SpiritualActionKind
{
    public const EXORCISM = 'exorcism';
    public const CONSECRATION = 'consecration';
    public const CRUSADE_PREACHING = 'crusade_preaching';
    public const INDULGENCED_RITE = 'indulgenced_rite';
    public const EMERGENCY_ORDERS = 'emergency_orders';
    public const RELIC_TRANSLATION = 'relic_translation';

    public static function all(): array
    {
        return [
            self::EXORCISM,
            self::CONSECRATION,
            self::CRUSADE_PREACHING,
            self::INDULGENCED_RITE,
            self::EMERGENCY_ORDERS,
            self::RELIC_TRANSLATION,
        ];
    }
}
