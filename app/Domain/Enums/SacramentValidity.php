<?php

namespace App\Domain\Enums;

final class SacramentValidity
{
    public const VALID = 'valid';
    public const VALID_ILLICIT = 'valid_illicit';
    public const INVALID = 'invalid';
    public const DOUBTFUL = 'doubtful';

    public static function all(): array
    {
        return [self::VALID, self::VALID_ILLICIT, self::INVALID, self::DOUBTFUL];
    }

    public static function confersGrace(string $validity): bool
    {
        return in_array($validity, [self::VALID, self::VALID_ILLICIT], true);
    }
}
