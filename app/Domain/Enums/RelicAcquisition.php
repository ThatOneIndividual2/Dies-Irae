<?php

namespace App\Domain\Enums;

final class RelicAcquisition
{
    public const GRANT = 'grant';
    public const GIFT = 'gift';
    public const TRANSLATION = 'translation';
    public const FIND = 'find';
    public const THEFT = 'theft';
    public const LOOT = 'loot';
    public const DEPOSIT = 'deposit';

    public static function all(): array
    {
        return [
            self::GRANT,
            self::GIFT,
            self::TRANSLATION,
            self::FIND,
            self::THEFT,
            self::LOOT,
            self::DEPOSIT,
        ];
    }

    public static function isLawful(string $acquisition): bool
    {
        return in_array($acquisition, [self::GRANT, self::GIFT, self::TRANSLATION, self::FIND, self::DEPOSIT], true);
    }
}
