<?php

namespace App\Domain\Enums;

final class EucharistStanding
{
    public const IN_COMMUNION = 'in_communion';
    public const BARRED_BY_SIN = 'barred_by_sin';
    public const BARRED_BY_CENSURE = 'barred_by_censure';
    public const UNBAPTIZED = 'unbaptized';

    public static function all(): array
    {
        return [self::IN_COMMUNION, self::BARRED_BY_SIN, self::BARRED_BY_CENSURE, self::UNBAPTIZED];
    }

    public static function mayReceive(string $standing): bool
    {
        return $standing === self::IN_COMMUNION;
    }
}
