<?php

namespace App\Domain\Enums;

final class MiracleReading
{
    public const DIVINE = 'divine';
    public const NATURAL = 'natural';
    public const DEMONIC = 'demonic';
    public const FRAUD = 'fraud';
    public const UNKNOWN = 'unknown';

    public static function all(): array
    {
        return [
            self::DIVINE,
            self::NATURAL,
            self::DEMONIC,
            self::FRAUD,
            self::UNKNOWN,
        ];
    }
}
