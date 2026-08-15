<?php

namespace App\Domain\Enums;

final class QuarantineLevel
{
    public const NONE = 'none';
    public const WATCH = 'watch';
    public const CLOSED_GATES = 'closed_gates';
    public const CORDON = 'cordon';

    public static function all(): array
    {
        return [
            self::NONE,
            self::WATCH,
            self::CLOSED_GATES,
            self::CORDON,
        ];
    }

    /**
     * How much civic quarantine blocks each spread vector, in basis points.
     * Armies ignore most civic measures. Pilgrims and traders do not.
     */
    public static function vectorBlock(string $level, string $vector): int
    {
        $table = [
            self::NONE => [
                SpreadVector::ADJACENT => 0,
                SpreadVector::TRADE => 0,
                SpreadVector::PILGRIMAGE => 0,
                SpreadVector::ARMY => 0,
            ],
            self::WATCH => [
                SpreadVector::ADJACENT => 800,
                SpreadVector::TRADE => 1500,
                SpreadVector::PILGRIMAGE => 2000,
                SpreadVector::ARMY => 200,
            ],
            self::CLOSED_GATES => [
                SpreadVector::ADJACENT => 2500,
                SpreadVector::TRADE => 7000,
                SpreadVector::PILGRIMAGE => 6500,
                SpreadVector::ARMY => 800,
            ],
            self::CORDON => [
                SpreadVector::ADJACENT => 5500,
                SpreadVector::TRADE => 9000,
                SpreadVector::PILGRIMAGE => 8500,
                SpreadVector::ARMY => 1500,
            ],
        ];

        return $table[$level][$vector] ?? 0;
    }

    public static function localContactReduction(string $level): int
    {
        return match ($level) {
            self::WATCH => 800,
            self::CLOSED_GATES => 1800,
            self::CORDON => 3200,
            default => 0,
        };
    }
}
