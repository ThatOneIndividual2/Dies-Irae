<?php

namespace App\Domain\Ai\Enums;

final class ActorType
{
    public const KING = 'king';
    public const DUKE = 'duke';
    public const COUNT = 'count';
    public const BISHOP = 'bishop';
    public const POPE = 'pope';
    public const ABBOT = 'abbot';
    public const HOLY_ORDER_LEADER = 'holy_order_leader';
    public const MILITARY_COMMANDER = 'military_commander';
    public const CLAIMANT = 'claimant';
    public const MERCHANT = 'merchant';
    public const HERETIC_LEADER = 'heretic_leader';
    public const CULT_LEADER = 'cult_leader';
    public const DEMON_COMMANDER = 'demon_commander';

    public const REALM = 'realm';
    public const MONASTERY = 'monastery';
    public const HOLY_ORDER = 'holy_order';
    public const CULT = 'cult';
    public const PAPACY = 'papacy';

    public static function characters(): array
    {
        return [
            self::KING,
            self::DUKE,
            self::COUNT,
            self::BISHOP,
            self::POPE,
            self::ABBOT,
            self::HOLY_ORDER_LEADER,
            self::MILITARY_COMMANDER,
            self::CLAIMANT,
            self::MERCHANT,
            self::HERETIC_LEADER,
            self::CULT_LEADER,
            self::DEMON_COMMANDER,
        ];
    }

    public static function organizations(): array
    {
        return [
            self::REALM,
            self::MONASTERY,
            self::HOLY_ORDER,
            self::CULT,
            self::PAPACY,
        ];
    }

    public static function all(): array
    {
        return array_merge(self::characters(), self::organizations());
    }

    public static function grain(string $type): string
    {
        if (in_array($type, self::organizations(), true)) {
            return ActorGrain::ORGANIZATION;
        }
        if (in_array($type, self::characters(), true)) {
            return ActorGrain::CHARACTER;
        }

        throw new \InvalidArgumentException("Unknown AI actor type: {$type}");
    }
}
