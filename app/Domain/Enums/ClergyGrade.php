<?php

namespace App\Domain\Enums;

final class ClergyGrade
{
    public const NONE = 'none';
    public const TONSURE = 'tonsure';
    public const PORTER = 'porter';
    public const LECTOR = 'lector';
    public const ACOLYTE = 'acolyte';
    public const SUBDEACON = 'subdeacon';
    public const DEACON = 'deacon';
    public const PRIEST = 'priest';
    public const BISHOP = 'bishop';

    public static function all(): array
    {
        return [
            self::NONE,
            self::TONSURE,
            self::PORTER,
            self::LECTOR,
            self::ACOLYTE,
            self::SUBDEACON,
            self::DEACON,
            self::PRIEST,
            self::BISHOP,
        ];
    }

    public static function weight(string $grade): int
    {
        $weights = config('church.orders_grade_weight', []);

        return (int) ($weights[$grade] ?? 0);
    }

    public static function isMajor(string $grade): bool
    {
        $from = config('church.secular_succession.major_orders_from', self::DEACON);

        return self::weight($grade) >= self::weight($from);
    }
}
