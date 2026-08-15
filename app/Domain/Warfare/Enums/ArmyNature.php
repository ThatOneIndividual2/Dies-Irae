<?php

namespace App\Domain\Warfare\Enums;

final class ArmyNature
{
    public const HUMAN = 'human';
    public const CULT = 'cult';
    public const DEMONIC = 'demonic';
    public const HOLY_ORDER = 'holy_order';
    public const CORRUPTED = 'corrupted';

    public static function all(): array
    {
        return [
            self::HUMAN,
            self::CULT,
            self::DEMONIC,
            self::HOLY_ORDER,
            self::CORRUPTED,
        ];
    }
}
