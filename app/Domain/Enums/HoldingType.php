<?php

namespace App\Domain\Enums;

final class HoldingType
{
    public const CASTLE = 'castle';
    public const TOWN = 'town';
    public const VILLAGE = 'village';
    public const MONASTIC = 'monastic';
    public const MONASTERY = 'monastery';

    public static function all(): array
    {
        return [self::CASTLE, self::TOWN, self::VILLAGE, self::MONASTIC, self::MONASTERY];
    }
}
