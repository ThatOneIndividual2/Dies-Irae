<?php

namespace App\Domain\Warfare\Enums;

final class SupplyKind
{
    public const FOOD = 'food';
    public const PLUNDER = 'plunder';
    public const PORTAL = 'portal';
    public const TITHE = 'tithe';
    public const DEVOUR = 'devour';

    public static function all(): array
    {
        return [
            self::FOOD,
            self::PLUNDER,
            self::PORTAL,
            self::TITHE,
            self::DEVOUR,
        ];
    }
}
