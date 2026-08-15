<?php

namespace App\Domain\Enums;

final class RelicTrueNature
{
    public const AUTHENTIC = 'authentic';
    public const FORGED = 'forged';
    public const DOUBTFUL = 'doubtful';

    public static function all(): array
    {
        return [self::AUTHENTIC, self::FORGED, self::DOUBTFUL];
    }
}
