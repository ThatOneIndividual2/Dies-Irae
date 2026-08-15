<?php

namespace App\Domain\Hell\Enums;

final class KnowledgeFacet
{
    public const TRUE_IDENTITY = 'true_identity';
    public const RANK = 'rank';
    public const MOTIVES = 'motives';
    public const LOCATION = 'location';
    public const VULNERABILITIES = 'vulnerabilities';

    public static function all(): array
    {
        return [self::TRUE_IDENTITY, self::RANK, self::MOTIVES, self::LOCATION, self::VULNERABILITIES];
    }
}
