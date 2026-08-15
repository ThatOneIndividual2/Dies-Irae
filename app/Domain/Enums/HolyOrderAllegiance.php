<?php

namespace App\Domain\Enums;

final class HolyOrderAllegiance
{
    public const INDEPENDENT = 'independent';
    public const PAPAL = 'papal';
    public const ROYAL = 'royal';

    public static function all(): array
    {
        return [self::INDEPENDENT, self::PAPAL, self::ROYAL];
    }
}
