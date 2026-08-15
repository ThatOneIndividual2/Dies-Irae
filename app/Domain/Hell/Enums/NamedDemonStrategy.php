<?php

namespace App\Domain\Hell\Enums;

final class NamedDemonStrategy
{
    public const CORRUPTER = 'corrupter';
    public const CULT_ORGANIZER = 'cult_organizer';
    public const MILITARY_INVADER = 'military_invader';

    public static function all(): array
    {
        return [self::CORRUPTER, self::CULT_ORGANIZER, self::MILITARY_INVADER];
    }
}
