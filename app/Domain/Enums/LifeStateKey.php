<?php

namespace App\Domain\Enums;

final class LifeStateKey
{
    public const MARRIED = 'married';
    public const WIDOWED = 'widowed';
    public const SIMPLE_VOWS = 'simple_vows';
    public const SOLEMN_VOWS = 'solemn_vows';
    public const ORDAINED = 'ordained';
    public const BENEFICED = 'beneficed';
    public const IMPRISONED = 'imprisoned';
    public const EXILE = 'exile';
    public const HOLY_ORDER = 'holy_order';
    public const HERMIT = 'hermit';
    public const HERETIC = 'heretic';
    public const CULT_MEMBER = 'cult_member';
    public const IN_CLERGY = 'in_clergy';
    public const CLAIMANT = 'claimant';

    public static function all(): array
    {
        return [
            self::MARRIED,
            self::WIDOWED,
            self::SIMPLE_VOWS,
            self::SOLEMN_VOWS,
            self::ORDAINED,
            self::BENEFICED,
            self::IMPRISONED,
            self::EXILE,
            self::HOLY_ORDER,
            self::HERMIT,
            self::HERETIC,
            self::CULT_MEMBER,
            self::IN_CLERGY,
            self::CLAIMANT,
        ];
    }

    public static function isKnown(string $key): bool
    {
        return in_array($key, self::all(), true);
    }
}
