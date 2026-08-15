<?php

namespace App\Domain\Enums;

final class HolyOrderStatus
{
    public const FOUNDING = 'founding';
    public const ACTIVE = 'active';
    public const CONTROVERSIAL = 'controversial';
    public const SUPPRESSED = 'suppressed';
    public const EXCOMMUNICATED = 'excommunicated';
    public const DISSOLVED = 'dissolved';

    public static function all(): array
    {
        return [
            self::FOUNDING,
            self::ACTIVE,
            self::CONTROVERSIAL,
            self::SUPPRESSED,
            self::EXCOMMUNICATED,
            self::DISSOLVED,
        ];
    }

    public static function canDeploy(string $status): bool
    {
        return in_array($status, [self::FOUNDING, self::ACTIVE, self::CONTROVERSIAL], true);
    }
}
