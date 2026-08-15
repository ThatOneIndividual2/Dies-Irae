<?php

namespace App\Domain\Enums;

final class TheologicalLean
{
    public const REFORM = 'reform';
    public const CONSERVATIVE = 'conservative';
    public const MYSTIC = 'mystic';
    public const RIGORIST = 'rigorist';
    public const WORLDLY = 'worldly';

    public static function all(): array
    {
        return [
            self::REFORM,
            self::CONSERVATIVE,
            self::MYSTIC,
            self::RIGORIST,
            self::WORLDLY,
        ];
    }

    public static function opposed(string $a, string $b): bool
    {
        $pairs = [
            self::REFORM => self::CONSERVATIVE,
            self::CONSERVATIVE => self::REFORM,
            self::RIGORIST => self::WORLDLY,
            self::WORLDLY => self::RIGORIST,
        ];

        return ($pairs[$a] ?? null) === $b;
    }
}
