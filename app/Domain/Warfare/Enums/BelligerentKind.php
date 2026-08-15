<?php

namespace App\Domain\Warfare\Enums;

final class BelligerentKind
{
    public const REALM = 'realm';
    public const HOLY_ORDER = 'holy_order';
    public const CULT = 'cult';
    public const DEMONIC_FACTION = 'demonic_faction';
    public const CORRUPTED_HOST = 'corrupted_host';

    public static function all(): array
    {
        return [
            self::REALM,
            self::HOLY_ORDER,
            self::CULT,
            self::DEMONIC_FACTION,
            self::CORRUPTED_HOST,
        ];
    }

    public static function toArmyNature(string $kind): string
    {
        return match ($kind) {
            self::HOLY_ORDER => ArmyNature::HOLY_ORDER,
            self::CULT => ArmyNature::CULT,
            self::DEMONIC_FACTION => ArmyNature::DEMONIC,
            self::CORRUPTED_HOST => ArmyNature::CORRUPTED,
            default => ArmyNature::HUMAN,
        };
    }
}
