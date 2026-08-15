<?php

namespace App\Domain\Enums;

final class FamineStage
{
    public const NONE = 'none';
    public const SHORTAGE = 'shortage';
    public const HUNGER = 'hunger';
    public const FAMINE = 'famine';
    public const STARVATION = 'starvation';
    public const COLLAPSE = 'collapse';

    public static function all(): array
    {
        return [
            self::NONE,
            self::SHORTAGE,
            self::HUNGER,
            self::FAMINE,
            self::STARVATION,
            self::COLLAPSE,
        ];
    }

    public static function weight(string $stage): int
    {
        return match ($stage) {
            self::COLLAPSE => 5,
            self::STARVATION => 4,
            self::FAMINE => 3,
            self::HUNGER => 2,
            self::SHORTAGE => 1,
            default => 0,
        };
    }
}
