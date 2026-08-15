<?php

namespace App\Domain\Enums;

final class HolyOrderMemberRank
{
    public const GRAND_MASTER = 'grand_master';
    public const COMMANDER = 'commander';
    public const KNIGHT = 'knight';
    public const SERGEANT = 'sergeant';
    public const CHAPLAIN = 'chaplain';
    public const BROTHER = 'brother';

    public static function all(): array
    {
        return [
            self::GRAND_MASTER,
            self::COMMANDER,
            self::KNIGHT,
            self::SERGEANT,
            self::CHAPLAIN,
            self::BROTHER,
        ];
    }

    public static function isCombatant(string $rank): bool
    {
        return in_array($rank, [self::GRAND_MASTER, self::COMMANDER, self::KNIGHT, self::SERGEANT], true);
    }
}
