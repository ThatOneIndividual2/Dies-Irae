<?php

namespace App\Domain\Hell\Enums;

final class PossessionStage
{
    public const NONE = 'none';
    public const TEMPTATION = 'temptation';
    public const OPPRESSION = 'oppression';
    public const POSSESSION = 'possession';
    public const PACT = 'pact';

    public static function all(): array
    {
        return [
            self::NONE,
            self::TEMPTATION,
            self::OPPRESSION,
            self::POSSESSION,
            self::PACT,
        ];
    }

    public static function index(string $stage): int
    {
        $i = array_search($stage, self::all(), true);
        if ($i === false) {
            throw new \InvalidArgumentException("Unknown possession stage: {$stage}");
        }

        return (int) $i;
    }

    public static function shift(string $stage, int $delta): string
    {
        $all = self::all();
        $next = max(0, min(count($all) - 1, self::index($stage) + $delta));

        return $all[$next];
    }
}
