<?php

namespace App\Domain\Enums;

final class RelicCondition
{
    public const INTACT = 'intact';
    public const DAMAGED = 'damaged';
    public const DESECRATED = 'desecrated';
    public const DESTROYED = 'destroyed';

    public static function all(): array
    {
        return [self::INTACT, self::DAMAGED, self::DESECRATED, self::DESTROYED];
    }

    public static function exists(string $condition): bool
    {
        return $condition !== self::DESTROYED;
    }
}
