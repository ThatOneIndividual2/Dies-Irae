<?php

namespace App\Domain\Enums;

final class HolyOrdersGrade
{
    public const NONE = 'none';
    public const MINOR = 'minor';
    public const DEACON = 'deacon';
    public const PRIEST = 'priest';
    public const BISHOP = 'bishop';

    public static function all(): array
    {
        return [self::NONE, self::MINOR, self::DEACON, self::PRIEST, self::BISHOP];
    }

    public static function weight(string $grade): int
    {
        return match ($grade) {
            self::BISHOP => 40,
            self::PRIEST => 30,
            self::DEACON => 20,
            self::MINOR => 10,
            default => 0,
        };
    }

    public static function mayMinisterEucharist(string $grade): bool
    {
        return self::weight($grade) >= self::weight(self::PRIEST);
    }

    public static function mayOrdain(string $grade): bool
    {
        return $grade === self::BISHOP;
    }
}
