<?php

namespace App\Domain\Enums;

final class SettlementKind
{
    public const CITY = 'city';
    public const TOWN = 'town';
    public const VILLAGE = 'village';
    public const MONASTERY = 'monastery';
    public const PORT = 'port';
    public const CAMP = 'camp';

    public static function all(): array
    {
        return [
            self::CITY,
            self::TOWN,
            self::VILLAGE,
            self::MONASTERY,
            self::PORT,
            self::CAMP,
        ];
    }

    /**
     * Local contact intensity in basis points. Cities and camps mix harder.
     */
    public static function contactIntensity(string $kind): int
    {
        return match ($kind) {
            self::CITY => 10000,
            self::PORT => 11000,
            self::TOWN => 7500,
            self::MONASTERY => 12000,
            self::CAMP => 13000,
            self::VILLAGE => 4500,
            default => 7000,
        };
    }
}
