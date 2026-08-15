<?php

namespace App\Domain\Warfare\Enums;

final class UnitCategory
{
    public const LEVY = 'levy';
    public const MEN_AT_ARMS = 'men_at_arms';
    public const CONSECRATED = 'consecrated';
    public const CULTIST = 'cultist';
    public const DEMONIC = 'demonic';
    public const CORRUPTED = 'corrupted';
    public const SIEGE = 'siege';

    public static function all(): array
    {
        return [
            self::LEVY,
            self::MEN_AT_ARMS,
            self::CONSECRATED,
            self::CULTIST,
            self::DEMONIC,
            self::CORRUPTED,
            self::SIEGE,
        ];
    }
}
