<?php

namespace App\Domain\Enums;

final class SaintOrigin
{
    public const HISTORICAL = 'historical';
    public const CHARACTER = 'character';

    public static function all(): array
    {
        return [self::HISTORICAL, self::CHARACTER];
    }
}
