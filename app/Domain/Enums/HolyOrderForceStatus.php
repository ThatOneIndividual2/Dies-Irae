<?php

namespace App\Domain\Enums;

final class HolyOrderForceStatus
{
    public const GARRISON = 'garrison';
    public const DEPLOYED = 'deployed';
    public const DISBANDED = 'disbanded';

    public static function all(): array
    {
        return [self::GARRISON, self::DEPLOYED, self::DISBANDED];
    }

    public static function isActive(string $status): bool
    {
        return in_array($status, [self::GARRISON, self::DEPLOYED], true);
    }
}
