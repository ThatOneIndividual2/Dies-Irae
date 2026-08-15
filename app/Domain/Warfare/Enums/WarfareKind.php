<?php

namespace App\Domain\Warfare\Enums;

final class WarfareKind
{
    public const HUMAN_VS_HUMAN = 'human_vs_human';
    public const HUMAN_VS_CULT = 'human_vs_cult';
    public const HUMAN_VS_DEMON = 'human_vs_demon';
    public const HOLY_ORDER = 'holy_order';
    public const CORRUPTED_ARMY = 'corrupted_army';

    public static function all(): array
    {
        return [
            self::HUMAN_VS_HUMAN,
            self::HUMAN_VS_CULT,
            self::HUMAN_VS_DEMON,
            self::HOLY_ORDER,
            self::CORRUPTED_ARMY,
        ];
    }

    public static function isKnown(string $kind): bool
    {
        return in_array($kind, self::all(), true);
    }
}
