<?php

namespace App\Domain\Enums;

final class CareerKey
{
    public const NONE = 'none';

    public const COURTIER = 'courtier';
    public const PAGE = 'page';
    public const SQUIRE = 'squire';
    public const KNIGHT = 'knight';
    public const HOUSEHOLD_OFFICER = 'household_officer';
    public const STEWARD = 'steward';
    public const MARSHAL = 'marshal';
    public const DIPLOMAT = 'diplomat';
    public const MAGISTRATE = 'magistrate';
    public const LANDED_NOBLE = 'landed_noble';
    public const MERCENARY_CAPTAIN = 'mercenary_captain';
    public const SCHOLAR = 'scholar';
    public const PHYSICIAN = 'physician';
    public const MERCHANT = 'merchant';

    public const NOVICE = 'novice';
    public const MONK = 'monk';
    public const PRIEST = 'priest';
    public const ABBOT = 'abbot';
    public const CANON = 'canon';
    public const BISHOP = 'bishop';
    public const ARCHBISHOP = 'archbishop';
    public const CARDINAL = 'cardinal';
    public const PAPAL_OFFICIAL = 'papal_official';
    public const THEOLOGIAN = 'theologian';
    public const INQUISITOR = 'inquisitor';
    public const EXORCIST = 'exorcist';
    public const CHAPLAIN = 'chaplain';

    public static function secular(): array
    {
        return [
            self::COURTIER,
            self::PAGE,
            self::SQUIRE,
            self::KNIGHT,
            self::HOUSEHOLD_OFFICER,
            self::STEWARD,
            self::MARSHAL,
            self::DIPLOMAT,
            self::MAGISTRATE,
            self::LANDED_NOBLE,
            self::MERCENARY_CAPTAIN,
            self::SCHOLAR,
            self::PHYSICIAN,
            self::MERCHANT,
        ];
    }

    public static function clerical(): array
    {
        return [
            self::NOVICE,
            self::MONK,
            self::PRIEST,
            self::ABBOT,
            self::CANON,
            self::BISHOP,
            self::ARCHBISHOP,
            self::CARDINAL,
            self::PAPAL_OFFICIAL,
            self::THEOLOGIAN,
            self::INQUISITOR,
            self::EXORCIST,
            self::CHAPLAIN,
        ];
    }

    public static function all(): array
    {
        return array_merge([self::NONE], self::secular(), self::clerical());
    }

    public static function isClerical(string $key): bool
    {
        return in_array($key, self::clerical(), true);
    }

    public static function isKnown(string $key): bool
    {
        return in_array($key, self::all(), true);
    }
}
