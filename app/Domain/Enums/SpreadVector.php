<?php

namespace App\Domain\Enums;

final class SpreadVector
{
    public const ADJACENT = 'adjacent';
    public const TRADE = 'trade';
    public const PILGRIMAGE = 'pilgrimage';
    public const ARMY = 'army';

    public const CLERGY = 'clergy';
    public const PREACHING = 'preaching';
    public const MIGRATION = 'migration';
    public const TRADE_ROUTES = 'trade_routes';
    public const NOBLE_PATRONAGE = 'noble_patronage';
    public const WAR = 'war';
    public const FAMINE = 'famine';
    public const PLAGUE = 'plague';
    public const DESPAIR = 'despair';
    public const CORRUPTION = 'corruption';
    public const CHARISMATIC_LEADERS = 'charismatic_leaders';

    public static function all(): array
    {
        return array_merge(self::plagueVectors(), self::heresyVectors());
    }

    public static function plagueVectors(): array
    {
        return [
            self::ADJACENT,
            self::TRADE,
            self::PILGRIMAGE,
            self::ARMY,
        ];
    }

    public static function heresyVectors(): array
    {
        return [
            self::CLERGY,
            self::PREACHING,
            self::MIGRATION,
            self::TRADE_ROUTES,
            self::NOBLE_PATRONAGE,
            self::WAR,
            self::FAMINE,
            self::PLAGUE,
            self::DESPAIR,
            self::CORRUPTION,
            self::CHARISMATIC_LEADERS,
        ];
    }
}
