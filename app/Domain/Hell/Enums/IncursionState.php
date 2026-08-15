<?php

namespace App\Domain\Hell\Enums;

final class IncursionState
{
    public const DORMANT = 'dormant';
    public const TEMPTED = 'tempted';
    public const CORRUPTED = 'corrupted';
    public const MANIFESTED = 'manifested';
    public const BREACHED = 'breached';
    public const OVERRUN = 'overrun';
    public const INFERNAL_STRONGHOLD = 'infernal_stronghold';

    public static function all(): array
    {
        return [
            self::DORMANT,
            self::TEMPTED,
            self::CORRUPTED,
            self::MANIFESTED,
            self::BREACHED,
            self::OVERRUN,
            self::INFERNAL_STRONGHOLD,
        ];
    }

    public static function index(string $state): int
    {
        $i = array_search($state, self::all(), true);

        if ($i === false) {
            throw new \InvalidArgumentException("Unknown incursion state: {$state}");
        }

        return (int) $i;
    }

    public static function at(int $index): string
    {
        $all = self::all();
        $clamped = max(0, min($index, count($all) - 1));

        return $all[$clamped];
    }

    public static function isAtLeast(string $state, string $minimum): bool
    {
        return self::index($state) >= self::index($minimum);
    }

    public static function shift(string $state, int $delta): string
    {
        return self::at(self::index($state) + $delta);
    }

    /**
     * Coarse map overlay. Incursion state remains the mechanical truth.
     */
    public static function overlayKind(string $state): string
    {
        return match ($state) {
            self::DORMANT => 'ordinary',
            self::TEMPTED => 'blighted',
            self::CORRUPTED => 'haunted',
            self::MANIFESTED, self::BREACHED => 'rifted',
            self::OVERRUN, self::INFERNAL_STRONGHOLD => 'occupied_by_hell',
            default => 'ordinary',
        };
    }
}
