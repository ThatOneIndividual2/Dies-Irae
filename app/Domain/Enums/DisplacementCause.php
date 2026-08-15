<?php

namespace App\Domain\Enums;

final class DisplacementCause
{
    public const PLAGUE = 'plague';
    public const FAMINE = 'famine';
    public const WAR = 'war';
    public const DESPAIR = 'despair';
    public const HELL = 'hell';
    public const EVICTION = 'eviction';
    public const RUIN = 'ruin';

    public static function all(): array
    {
        return [
            self::PLAGUE,
            self::FAMINE,
            self::WAR,
            self::DESPAIR,
            self::HELL,
            self::EVICTION,
            self::RUIN,
        ];
    }
}
